<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

use InvalidArgumentException;
use Lacasera\ElasticBridge\Exceptions\InvalidQuery;
use Meilisearch\Client;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Exceptions\ApiException;
use Override;
use RuntimeException;

class MeilisearchConnection implements ConnectionInterface
{
    private readonly Client $client;

    private readonly MeilisearchQueryTranslator $translator;

    private readonly string $primaryKey;

    public function __construct(?Client $client = null, ?MeilisearchQueryTranslator $translator = null)
    {
        $host = (string) config('elasticbridge.meilisearch.host', 'http://127.0.0.1:7700');
        $key = config('elasticbridge.meilisearch.key');

        $this->client = $client ?? new Client($host, is_string($key) ? $key : null);
        $this->translator = $translator ?? new MeilisearchQueryTranslator;
        $this->primaryKey = (string) config('elasticbridge.meilisearch.primary_key', 'id');

        if ($this->primaryKey === '') {
            throw new InvalidArgumentException('Meilisearch primary_key cannot be empty.');
        }
    }

    #[Override]
    public function search(array $params): array
    {
        $index = $this->indexName($params);
        $body = (array) ($params['body'] ?? []);
        $translated = $this->translator->translate($body, $this->primaryKey);

        if (($documents = $this->documentsByIds($index, $body)) !== null) {
            return $this->normalizeSearch($index, $documents, $body);
        }

        $response = $this->client->index($index)->rawSearch($translated['query'], $translated['options']);

        return $this->normalizeSearch($index, $response, $body);
    }

    private function normalizeSearch(string $index, array $response, array $body): array
    {
        $total = (int) ($response['totalHits'] ?? $response['estimatedTotalHits'] ?? count($response['hits'] ?? []));
        $hits = [];

        foreach ($response['hits'] ?? [] as $document) {
            if (! array_key_exists($this->primaryKey, $document)) {
                throw new RuntimeException(sprintf('Meilisearch result is missing primary key [%s].', $this->primaryKey));
            }

            $source = $document;
            $score = $source['_rankingScore'] ?? null;
            unset($source['_rankingScore'], $source['_rankingScoreDetails'], $source['_formatted'], $source['_matchesPosition']);

            if (isset($body['_source']) && ! in_array($this->primaryKey, $body['_source'], true)) {
                unset($source[$this->primaryKey]);
            }

            $hits[] = [
                '_id' => $document[$this->primaryKey],
                '_index' => $index,
                '_score' => is_numeric($score) ? (float) $score : null,
                '_source' => $source,
            ];
        }

        $offset = (int) ($body['from'] ?? $response['offset'] ?? 0);
        $limit = (int) ($body['size'] ?? $response['limit'] ?? 20);

        return [
            'hits' => [
                'total' => ['value' => $total, 'relation' => 'eq'],
                'hits' => $hits,
            ],
            'pagination' => [
                'previous' => $offset > 0 ? max(0, $offset - $limit) : [],
                'next' => $limit > 0 && $hits !== [] && $offset + count($hits) < $total ? $offset + $limit : [],
                'total' => $total,
            ],
        ];
    }

    private function documentsByIds(string $index, array $body): ?array
    {
        if (array_diff(array_keys($body), ['query', '_source', 'size', 'from']) !== [] || ! isset($body['query']['ids'])) {
            return null;
        }

        $ids = $body['query']['ids']['values'] ?? null;

        if (! is_array($ids) || $ids === []) {
            throw new InvalidQuery('Meilisearch ids query requires a non-empty list of ids.');
        }

        foreach ($ids as $id) {
            if (! is_string($id) && ! is_int($id)) {
                throw new InvalidQuery('Meilisearch ids must be strings or integers.');
            }
        }

        $query = (new DocumentsQuery)->setIds($ids)->setLimit($body['size'] ?? count($ids));

        if (isset($body['from'])) {
            $query->setOffset($body['from']);
        }

        if (isset($body['_source'])) {
            $query->setFields(array_values(array_unique([...$body['_source'], $this->primaryKey])));
        }

        $response = $this->client->index($index)->getDocuments($query);

        return [
            'hits' => $response->getResults(),
            'totalHits' => $response->getTotal(),
            'offset' => $response->getOffset(),
            'limit' => $response->getLimit(),
        ];
    }

    #[Override]
    public function count(array $params): array
    {
        $index = $this->indexName($params);
        $body = (array) ($params['body'] ?? []);
        $translated = $this->translator->translate($body, $this->primaryKey);

        if (($documents = $this->documentsByIds($index, $body)) !== null) {
            return ['count' => (int) $documents['totalHits']];
        }

        if ($translated['query'] === null && ! isset($translated['options']['filter'])) {
            return ['count' => (int) $this->client->index($index)->stats()['numberOfDocuments']];
        }

        $options = $translated['options'];
        unset($options['offset'], $options['limit'], $options['sort'], $options['attributesToRetrieve']);
        $options['page'] = 1;
        $options['hitsPerPage'] = 1;
        $response = $this->client->index($index)->rawSearch($translated['query'], $options);

        if (! isset($response['totalHits'])) {
            throw new RuntimeException('Meilisearch did not return totalHits for the count query.');
        }

        return ['count' => (int) $response['totalHits']];
    }

    #[Override]
    public function index(array $params): array
    {
        $index = $this->indexName($params);
        $body = $params['body'] ?? null;

        if (! is_array($body)) {
            throw new InvalidArgumentException('Meilisearch indexing requires a document body.');
        }

        $id = $this->documentId($body, $params['id'] ?? null);
        $this->await($this->client->index($index)->addDocuments([$body], $this->primaryKey));

        return ['_id' => $id, '_index' => $index, 'result' => 'created'];
    }

    #[Override]
    public function update(array $params): bool
    {
        $index = $this->indexName($params);
        $id = $params['id'] ?? null;
        $body = $params['body'] ?? [];

        if (! is_string($id) && ! is_int($id)) {
            throw new InvalidArgumentException('Meilisearch updates require an id.');
        }

        if (! is_array($body) || array_diff(array_keys($body), ['doc', 'doc_as_upsert']) !== [] || ! is_array($body['doc'] ?? null)) {
            throw new InvalidQuery('Meilisearch updates support only a doc payload; scripts are unavailable.');
        }

        $endpoint = $this->client->index($index);

        if (! ($body['doc_as_upsert'] ?? false)) {
            try {
                $endpoint->getDocument($id, [$this->primaryKey]);
            } catch (ApiException $exception) {
                if ($exception->httpStatus === 404) {
                    return false;
                }

                throw $exception;
            }
        }

        $document = $body['doc'];
        $this->documentId($document, $id);
        $this->await($endpoint->updateDocuments([$document], $this->primaryKey));

        return true;
    }

    #[Override]
    public function bulk(array $params): array
    {
        $body = $params['body'] ?? [];

        if (! is_array($body) || count($body) % 2 !== 0) {
            throw new InvalidArgumentException('Meilisearch bulk requires action/document pairs.');
        }

        if ($body === []) {
            return ['errors' => false, 'items' => []];
        }

        $documents = [];
        $metadata = [];
        $index = null;
        $action = null;

        foreach (array_chunk($body, 2) as [$operation, $source]) {
            if (! is_array($operation) || count($operation) !== 1 || ! is_array($source)) {
                throw new InvalidArgumentException('Meilisearch bulk requires valid action/document pairs.');
            }

            $currentAction = (string) array_key_first($operation);

            if (! in_array($currentAction, ['index', 'update'], true)) {
                throw new InvalidQuery(sprintf('Meilisearch bulk does not support [%s].', $currentAction));
            }

            $meta = $operation[$currentAction];

            if (! is_array($meta)) {
                throw new InvalidArgumentException('Meilisearch bulk action metadata must be an object.');
            }

            $currentIndex = $this->indexName(['index' => $meta['_index'] ?? null]);

            if (($index !== null && $index !== $currentIndex) || ($action !== null && $action !== $currentAction)) {
                throw new InvalidQuery('Meilisearch bulk requires one index and action per request.');
            }

            $index = $currentIndex;
            $action = $currentAction;

            if ($currentAction === 'update') {
                if (! ($source['doc_as_upsert'] ?? false) || ! is_array($source['doc'] ?? null)) {
                    throw new InvalidQuery('Meilisearch bulk updates require doc_as_upsert and a doc object.');
                }

                $source = $source['doc'];
            }

            $id = $this->documentId($source, $meta['_id'] ?? null);
            $documents[] = $source;
            $metadata[] = ['_id' => $id, '_index' => $index];
        }

        $endpoint = $this->client->index($index);
        $task = $action === 'index'
            ? $endpoint->addDocuments($documents, $this->primaryKey)
            : $endpoint->updateDocuments($documents, $this->primaryKey);
        $completed = $this->wait($task);
        $failed = ($completed['status'] ?? null) !== 'succeeded';
        $items = [];

        foreach ($metadata as $meta) {
            $items[] = [$action => [
                ...$meta,
                'status' => $failed ? 500 : 200,
                'result' => $failed ? 'failed' : ($action === 'index' ? 'created' : 'updated'),
                ...($failed ? ['error' => $completed['error'] ?? ['message' => 'Meilisearch task failed.']] : []),
            ]];
        }

        return ['errors' => $failed, 'items' => $items];
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    private function indexName(array $params): string
    {
        $index = $params['index'] ?? null;

        if (! is_string($index) || $index === '' || str_contains($index, '*') || str_contains($index, ',')) {
            throw new InvalidQuery('Meilisearch requires one concrete index; wildcard and comma-separated indexes are unsupported.');
        }

        return $index;
    }

    private function documentId(array &$document, mixed $id): string|int
    {
        $id ??= $document[$this->primaryKey] ?? null;

        if (! is_string($id) && ! is_int($id)) {
            throw new InvalidArgumentException(sprintf('Meilisearch documents require a [%s] primary key.', $this->primaryKey));
        }

        if (array_key_exists($this->primaryKey, $document) && $document[$this->primaryKey] !== $id) {
            throw new InvalidArgumentException('Meilisearch document primary key differs from the requested id.');
        }

        $document[$this->primaryKey] = $id;

        return $id;
    }

    private function await(array $task): void
    {
        $completed = $this->wait($task);

        if (($completed['status'] ?? null) !== 'succeeded') {
            throw new RuntimeException((string) ($completed['error']['message'] ?? 'Meilisearch task failed.'));
        }
    }

    private function wait(array $task): array
    {
        if (! isset($task['taskUid'])) {
            throw new RuntimeException('Meilisearch did not return a task UID.');
        }

        return $this->client->waitForTask(
            $task['taskUid'],
            (int) config('elasticbridge.meilisearch.task_timeout_ms', 30000),
            (int) config('elasticbridge.meilisearch.task_poll_interval_ms', 50)
        );
    }
}
