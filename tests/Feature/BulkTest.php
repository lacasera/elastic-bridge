<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Feature;

use InvalidArgumentException;
use Lacasera\ElasticBridge\DTO\BulkResult;
use Lacasera\ElasticBridge\Exceptions\BulkLimitExceeded;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BulkTest extends TestCase
{
    #[Test]
    public function it_builds_index_actions_and_maps_id_to_underscore_id(): void
    {
        $fake = Room::fake([
            'took' => 1,
            'errors' => false,
            'items' => [
                ['index' => ['_id' => '1', 'status' => 201, 'result' => 'created']],
                ['index' => ['_id' => 'auto', 'status' => 201, 'result' => 'created']],
            ],
        ]);

        $result = Room::bulk([
            ['id' => 1, 'price' => 100],
            ['price' => 200],
        ]);

        $this->assertInstanceOf(BulkResult::class, $result);
        $this->assertSame(2, $result->count());
        $this->assertFalse($result->hasErrors());

        $body = $fake->requests['bulk'][0]['body'];

        $this->assertSame(['index' => ['_index' => 'rooms', '_id' => 1]], $body[0]);
        $this->assertSame(['price' => 100], $body[1]);
        $this->assertSame(['index' => ['_index' => 'rooms']], $body[2]);
        $this->assertSame(['price' => 200], $body[3]);
    }

    #[Test]
    public function it_builds_update_actions_with_doc_as_upsert(): void
    {
        $fake = Room::fake([
            'items' => [
                ['update' => ['_id' => '1', 'status' => 200, 'result' => 'updated']],
            ],
        ]);

        Room::upsert([
            ['id' => 1, 'price' => 150],
        ]);

        $body = $fake->requests['bulk'][0]['body'];

        $this->assertSame(['update' => ['_index' => 'rooms', '_id' => 1]], $body[0]);
        $this->assertSame(['doc' => ['price' => 150], 'doc_as_upsert' => true], $body[1]);
    }

    #[Test]
    public function upsert_requires_an_id_for_each_document(): void
    {
        Room::fake([]);

        $this->expectException(InvalidArgumentException::class);

        Room::upsert([
            ['price' => 10],
        ]);
    }

    #[Test]
    public function it_throws_when_the_record_cap_is_exceeded(): void
    {
        Room::fake([]);
        config()->set('elasticbridge.bulk.max', 2);

        $this->expectException(BulkLimitExceeded::class);

        Room::bulk([
            ['a' => 1],
            ['a' => 2],
            ['a' => 3],
        ]);
    }

    #[Test]
    public function it_chunks_large_payloads_into_multiple_requests(): void
    {
        $fake = Room::fake([
            'items' => [
                ['index' => ['_id' => 'x', 'status' => 201, 'result' => 'created']],
            ],
        ]);

        $result = Room::bulk([
            ['a' => 1],
            ['a' => 2],
            ['a' => 3],
        ], chunkSize: 2);

        // 3 rows in chunks of 2 → 2 bulk requests.
        $this->assertCount(2, $fake->requests['bulk']);

        // Items from every chunk are merged into one result (canned item per request).
        $this->assertSame(2, $result->total());
    }
}
