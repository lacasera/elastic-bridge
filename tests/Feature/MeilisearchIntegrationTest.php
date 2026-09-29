<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Feature;

use Http\Mock\Client as MockHttpClient;
use InvalidArgumentException;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Connection\MeilisearchConnection;
use Lacasera\ElasticBridge\Exceptions\InvalidQuery;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Meilisearch\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Override;
use PHPUnit\Framework\Attributes\Test;

class MeilisearchIntegrationTest extends TestCase
{
    private MockHttpClient $http;

    private MeilisearchConnection $connection;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('elasticbridge.meilisearch.host', 'http://meili.test:7700');
        config()->set('elasticbridge.meilisearch.key', 'secret');
        config()->set('elasticbridge.meilisearch.primary_key', 'id');

        $this->http = new MockHttpClient;
        $factory = new Psr17Factory;
        $client = new Client('http://meili.test:7700', 'secret', $this->http, $factory, [], $factory);
        $this->connection = new MeilisearchConnection($client);
        app()->instance(ConnectionInterface::class, $this->connection);
    }

    #[Test]
    public function it_searches_with_filters_sort_fields_and_offset_pagination(): void
    {
        $this->respond([
            'hits' => [['id' => 3, 'name' => 'Light', '_rankingScore' => 0.8]],
            'estimatedTotalHits' => 3,
            'offset' => 2,
            'limit' => 2,
        ]);

        $results = Room::asBoolean()
            ->mustMatch('name', 'light')
            ->filterByTerm('category', 'book')
            ->filterByRange('price', 10, 'gte')
            ->orderBy('price', 'DESC')
            ->simplePaginate(2, 2)
            ->get(['name']);

        $request = $this->http->getLastRequest();
        $body = json_decode((string) $request->getBody(), true);

        $this->assertSame('/indexes/rooms/search', $request->getUri()->getPath());
        $this->assertSame('Bearer secret', $request->getHeaderLine('Authorization'));
        $this->assertSame('light', $body['q']);
        $this->assertSame(['name'], $body['attributesToSearchOn']);
        $this->assertSame(['name', 'id'], $body['attributesToRetrieve']);
        $this->assertSame(['price:desc'], $body['sort']);
        $this->assertSame(2, $body['offset']);
        $this->assertSame(2, $body['limit']);
        $this->assertStringContainsString('category = "book"', $body['filter']);
        $this->assertStringContainsString('price >= 10', $body['filter']);
        $this->assertSame(3, $results->first()->id);
        $this->assertSame(0.8, $results->first()->getScore());
        $this->assertSame(['name' => 'Light'], $results->first()->toArray());
        $this->assertSame(['previous' => 0, 'next' => [], 'total' => 3], $results->links());
    }

    #[Test]
    public function it_finds_documents_by_id_without_requiring_a_filterable_primary_key(): void
    {
        $this->respond([
            'results' => [['id' => 7, 'name' => 'Light']],
            'offset' => 0,
            'limit' => 1,
            'total' => 1,
        ]);

        $room = Room::find(7);

        $this->assertSame(7, $room->id);
        $this->assertSame('Light', $room->name);
        $this->assertSame('/indexes/rooms/documents', $this->http->getLastRequest()->getUri()->getPath());
        parse_str($this->http->getLastRequest()->getUri()->getQuery(), $query);
        $this->assertSame('7', $query['ids']);
    }

    #[Test]
    public function it_counts_requested_ids_without_a_filterable_primary_key(): void
    {
        $this->respond([
            'results' => [['id' => 7]],
            'offset' => 0,
            'limit' => 2,
            'total' => 1,
        ]);

        $this->assertSame(1, Room::asIds()->withValues([7, 8])->count());
        $this->assertSame('/indexes/rooms/documents', $this->http->getLastRequest()->getUri()->getPath());
    }

    #[Test]
    public function all_uses_offset_pagination_for_meilisearch(): void
    {
        $this->respond(['hits' => [['id' => 7, 'name' => 'Light']], 'estimatedTotalHits' => 1, 'offset' => 0, 'limit' => 1]);

        $results = Room::all(1);

        $this->assertSame(7, $results->first()->id);
        $this->assertSame(1, $results->total());
    }

    #[Test]
    public function it_uses_exact_document_stats_for_an_unfiltered_count(): void
    {
        $this->respond(['numberOfDocuments' => 42]);

        $this->assertSame(42, Room::count());
        $this->assertSame('/indexes/rooms/stats', $this->http->getLastRequest()->getUri()->getPath());
    }

    #[Test]
    public function it_requests_exhaustive_hits_for_a_filtered_count(): void
    {
        $this->respond(['hits' => [], 'totalHits' => 7, 'page' => 1, 'hitsPerPage' => 1]);

        $this->assertSame(7, Room::asBoolean()->filterByTerm('category', 'book')->count());

        $body = json_decode((string) $this->http->getLastRequest()->getBody(), true);
        $this->assertSame(1, $body['page']);
        $this->assertSame(1, $body['hitsPerPage']);
        $this->assertStringContainsString('category = "book"', $body['filter']);
    }

    #[Test]
    public function it_indexes_a_document_and_waits_for_the_task(): void
    {
        $this->respond(['taskUid' => 11]);
        $this->respond(['status' => 'succeeded']);

        $this->assertSame(7, Room::create(['id' => 7, 'name' => 'Light']));

        $requests = $this->http->getRequests();
        $this->assertSame('POST', $requests[0]->getMethod());
        $this->assertSame('/indexes/rooms/documents', $requests[0]->getUri()->getPath());
        $this->assertSame([['name' => 'Light', 'id' => 7]], json_decode((string) $requests[0]->getBody(), true));
        $this->assertSame('/tasks/11', $requests[1]->getUri()->getPath());
    }

    #[Test]
    public function it_partially_updates_an_existing_document(): void
    {
        $this->respond(['id' => 7]);
        $this->respond(['taskUid' => 12]);
        $this->respond(['status' => 'succeeded']);

        $this->assertTrue($this->connection->update([
            'index' => 'rooms',
            'id' => 7,
            'body' => ['doc' => ['name' => 'Updated']],
        ]));

        $requests = $this->http->getRequests();
        $this->assertSame('/indexes/rooms/documents/7', $requests[0]->getUri()->getPath());
        $this->assertSame('PUT', $requests[1]->getMethod());
        $this->assertSame([['name' => 'Updated', 'id' => 7]], json_decode((string) $requests[1]->getBody(), true));
    }

    #[Test]
    public function it_saves_a_hydrated_bridge(): void
    {
        $documentPage = [
            'results' => [['id' => 7, 'name' => 'Light']],
            'offset' => 0,
            'limit' => 1,
            'total' => 1,
        ];

        $this->respond($documentPage);
        $this->respond($documentPage);
        $this->respond(['id' => 7]);
        $this->respond(['taskUid' => 15]);
        $this->respond(['status' => 'succeeded']);

        $room = Room::find(7);
        $room->name = 'Updated';

        $this->assertTrue($room->save());
        $this->assertSame(5, count($this->http->getRequests()));
        $this->assertSame('PUT', $this->http->getRequests()[3]->getMethod());
    }

    #[Test]
    public function it_reports_a_failed_bulk_task_in_bulk_result(): void
    {
        $this->respond(['taskUid' => 13]);
        $this->respond(['status' => 'failed', 'error' => ['code' => 'invalid_document', 'message' => 'Bad document']]);

        $result = Room::bulk([['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']]);

        $this->assertSame(2, $result->total());
        $this->assertSame(0, $result->count());
        $this->assertTrue($result->hasErrors());
        $this->assertSame('invalid_document', $result->failed()->first()['error']['code']);
    }

    #[Test]
    public function it_upserts_documents_with_the_meilisearch_primary_key(): void
    {
        $this->respond(['taskUid' => 14]);
        $this->respond(['status' => 'succeeded']);

        $result = Room::upsert([['id' => 4, 'name' => 'New']]);

        $this->assertSame(1, $result->count());
        $request = $this->http->getRequests()[0];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame([['name' => 'New', 'id' => 4]], json_decode((string) $request->getBody(), true));
    }

    #[Test]
    public function it_requires_a_primary_key_before_sending_a_write(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('primary key');

        Room::create(['name' => 'No id']);
    }

    #[Test]
    public function it_maps_bridge_ids_to_a_custom_primary_key(): void
    {
        config()->set('elasticbridge.meilisearch.primary_key', 'objectID');
        app()->instance(ConnectionInterface::class, new MeilisearchConnection($this->connection->getClient()));
        $this->respond(['taskUid' => 16]);
        $this->respond(['status' => 'succeeded']);

        $this->assertSame('room-7', Room::create(['id' => 'room-7', 'name' => 'Light']));

        $request = $this->http->getRequests()[0];
        $this->assertSame([['name' => 'Light', 'objectID' => 'room-7']], json_decode((string) $request->getBody(), true));
    }

    #[Test]
    public function it_rejects_queries_with_no_safe_meilisearch_equivalent(): void
    {
        try {
            Room::asBoolean()->matchAll()->cursorPaginate();
            $this->fail('Cursor pagination should be unavailable.');
        } catch (InvalidQuery $exception) {
            $this->assertStringContainsString('simplePaginate', $exception->getMessage());
        }

        try {
            Room::asFuzzy()->withValues('light', 'name')->get();
            $this->fail('Fuzzy DSL should be unavailable.');
        } catch (InvalidQuery $exception) {
            $this->assertStringContainsString('[fuzzy]', $exception->getMessage());
        }

        try {
            Room::from(['rooms', 'other_rooms'])->asBoolean()->matchAll()->get();
            $this->fail('Multi-index searches should be unavailable.');
        } catch (InvalidQuery $exception) {
            $this->assertStringContainsString('one concrete index', $exception->getMessage());
        }

        try {
            Room::asBoolean()->matchAll()->withAggregate('avg', 'price')->get();
            $this->fail('Aggregations should be unavailable.');
        } catch (InvalidQuery $exception) {
            $this->assertStringContainsString('[aggs]', $exception->getMessage());
        }

        $this->assertCount(0, $this->http->getRequests());
    }

    #[Test]
    public function it_preserves_a_nested_optional_text_clause_and_its_filter(): void
    {
        $this->respond(['hits' => [], 'estimatedTotalHits' => 0]);

        $this->connection->search([
            'index' => 'rooms',
            'body' => ['query' => ['bool' => ['should' => [
                ['bool' => ['must' => [
                    ['match' => ['name' => 'light']],
                    ['term' => ['category' => 'book']],
                ]]],
            ]]]],
        ]);

        $body = json_decode((string) $this->http->getLastRequest()->getBody(), true);
        $this->assertSame('light', $body['q']);
        $this->assertStringContainsString('category = "book"', $body['filter']);
    }

    #[Test]
    public function it_rejects_boolean_queries_that_would_drop_or_require_clauses(): void
    {
        $queries = [
            ['bool' => ['must_not' => [['match_all' => []]]]],
            ['bool' => ['should' => [['match_all' => []], ['term' => ['category' => 'book']]]]],
            ['bool' => [
                'must' => [['match_all' => []]],
                'should' => [['term' => ['category' => 'book']]],
            ]],
        ];

        foreach ($queries as $query) {
            try {
                $this->connection->search(['index' => 'rooms', 'body' => ['query' => $query]]);
                $this->fail('The query should be rejected before reaching Meilisearch.');
            } catch (InvalidQuery) {
                // These combinations cannot be translated without changing their meaning.
            }
        }

        $this->assertCount(0, $this->http->getRequests());
    }

    private function respond(array $body, int $status = 200): void
    {
        $this->http->addResponse(new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR)
        ));
    }
}
