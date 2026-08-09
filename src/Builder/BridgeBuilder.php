<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Builder;

use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;
use Lacasera\ElasticBridge\Concerns\HasAggregates;
use Lacasera\ElasticBridge\Concerns\SetsTerm;
use Lacasera\ElasticBridge\DTO\BulkResult;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Enums\OrderOperator;
use Lacasera\ElasticBridge\Exceptions\BulkLimitExceeded;
use Lacasera\ElasticBridge\Exceptions\InvalidQuery;
use Lacasera\ElasticBridge\Query\QueryBuilder;
use Lacasera\ElasticBridge\Query\Traits\HasFilters;

class BridgeBuilder implements BridgeBuilderInterface
{
    use ForwardsCalls;
    use HasAggregates;
    use HasFilters;
    use SetsTerm;

    protected ElasticBridge $bridge;

    protected QueryBuilder $query;

    private bool $isPaginating = false;

    private ?string $searchIndex = null;

    private ?string $writeIndex = null;

    public function __construct()
    {
        $this->query = app()->make(QueryBuilder::class);
    }

    /**
     * @return $this
     */
    public function setBridge(ElasticBridge $elasticBridge): static
    {
        $this->bridge = $elasticBridge;

        return $this;
    }

    public function getBridge(): ElasticBridge
    {
        return $this->bridge;
    }

    /**
     * Override the index (or wildcard/comma pattern) to search against.
     *
     * @param  string|array<int, string>  $index
     * @return $this
     */
    public function from(string|array $index): static
    {
        $this->searchIndex = is_array($index) ? implode(',', $index) : $index;

        return $this;
    }

    /**
     * Override the concrete index to write to (wins over a document's origin index).
     *
     * @return $this
     */
    public function into(string $index): static
    {
        $this->writeIndex = $index;

        return $this;
    }

    public function resolveSearchIndex(): string
    {
        return $this->searchIndex ?? $this->getBridge()->getSearchIndex();
    }

    public function resolveWriteIndex(): string
    {
        return $this->writeIndex ?? $this->getBridge()->getWriteIndex();
    }

    /**
     * Fetch a bounded first page of records using a match_all query.
     *
     * This intentionally does NOT request every document — passing the full
     * index count as the page size would exceed Elasticsearch's default
     * `max_result_window` (10,000) on any real index.
     */
    public function all(int $perPage = QueryBuilder::PAGINATION_SIZE, array $columns = ['*']): mixed
    {
        return $this->asBoolean()
            ->matchAll()
            ->cursorPaginate($perPage)
            ->get($columns);
    }

    /**
     * @return $this
     */
    public function shouldMatch(string $field, $value): self
    {
        $this->query->setPayload('should', ['match' => [$field => $value]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function take(int $size): static
    {
        $this->query->setPagination(['size' => $size]);

        return $this;
    }

    /**
     * @return $this
     */
    public function skip(int $size): self
    {
        $this->query->setPagination(['from' => $size]);

        return $this;
    }

    /**
     * @return $this
     */
    public function limit(int $size): self
    {
        $this->query->setPagination(['size' => $size]);

        return $this;
    }

    /**
     * @return $this
     */
    public function offset(int $size): self
    {
        $this->query->setPagination(['from' => $size]);

        return $this;
    }

    /**
     * @return $this
     */
    public function shouldMatchAll($boost = 1.0): self
    {
        $this->query->setPayload('should', ['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function mustMatch(string $field, $value): self
    {
        $this->asBoolean();

        $this->query->setPayload('must', ['match' => [
            $field => [
                'query' => $value,
            ],
        ]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function mustNot(string $query, string $field, array $payload): self
    {
        $this->query->setPayload('must_not', [
            $query => [
                $field => $payload,
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function must(string $query, string $field, string $value): self
    {
        $this->asBoolean();

        $this->query->setPayload('must', [
            $query => [
                $field => [
                    'query' => $value,
                ],
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function matchAll(float $boost = 1.0): self
    {
        $this->query->setPayload('must', ['match_all' => ['boost' => $boost]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function raw(array $query): self
    {
        $this->query->setRawPayload($query);

        return $this;
    }

    /**
     * @return $this
     */
    public function mustExist(string $field): self
    {
        $this->query->setPayload('must', ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function shouldExist(string $field): self
    {
        $this->query->setPayload(key: 'should', payload: ['exists' => ['field' => $field]]);

        return $this;
    }

    /**
     * @return $this
     */
    public function match(string $field, string $query, array $options = []): self
    {
        $this->query->setPayload(key: 'match', payload: [
            $field => [
                'query' => $query,
                ...$options,
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function orMatch(string $field, string $query): self
    {
        $this->query->setPayload(key: 'match', payload: [$field => [
            'query' => $query,
            'operator' => 'or',
        ]]);

        return $this;
    }

    /**
     * @return mixed
     */
    public function find($ids)
    {
        $this->query->setTerm('ids');

        $this->withValues($ids);

        return is_array($ids) ? $this->get() : $this->get()->first();
    }

    /**
     * @return $this
     */
    public function multiMatch($field, string $query): self
    {
        if (! is_array($field)) {
            $field = [$field];
        }

        // multi_match has no term-level of its own; nest it as a bool must
        // clause so it composes into valid DSL.
        $this->asBoolean();

        $this->query->setPayload('must', [
            'multi_match' => [
                'query' => $query,
                'fields' => $field,
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function matchPhrase(string $field, string $query, array $options = []): self
    {
        // match_phrase has no term-level of its own; nest it as a bool must
        // clause so it composes into valid DSL.
        $this->asBoolean();

        $this->query->setPayload('must', [
            'match_phrase' => [
                $field => [
                    'query' => $query,
                    ...$options,
                ],
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function withValues($values, ?string $field = null, array $options = []): self
    {
        if (! $field) {
            $this->query->setPayload('values', $values);

            return $this;
        }

        $this->query->setPayload(key: $field, payload: ['values' => $values, ...$options]);

        return $this;
    }

    /**
     * @param  string[]  $columns
     */
    public function get(array $columns = ['*']): mixed
    {
        $builder = clone $this;

        return $builder->getBridges($columns);
    }

    public function count(): int
    {
        return $this->query->count($this->resolveSearchIndex());
    }

    /**
     * @return $this
     */
    public function simplePaginate(int $size = QueryBuilder::PAGINATION_SIZE, int $from = 0): self
    {
        $this->query->setPagination(['from' => $from, 'size' => $size]);
        $this->isPaginating = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function cursorPaginate(int $size = QueryBuilder::PAGINATION_SIZE, array $sort = []): self
    {
        $paginate['size'] = $size;

        if ($sort !== []) {
            $paginate['search_after'] = $sort;
        }

        $this->isPaginating = true;
        $this->query->setPagination($paginate);

        return $this;
    }

    /**
     * @return $this
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $normalized = strtolower($direction);

        if (! OrderOperator::isValid($normalized)) {
            throw new InvalidQuery(
                sprintf('invalid order direction [%s]. allowed: %s.', $direction, implode(', ', OrderOperator::values()))
            );
        }

        $this->query->setSort([
            $field => [
                'order' => $normalized,
            ],
        ]);

        return $this;
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function getBridges(array $columns = ['*']): mixed
    {
        return $this->bridge->hydrate(
            $this->query->get($this->resolveSearchIndex(), $columns),
            $this->isPaginating
        );
    }

    /**
     * @return array[]|false|string
     */
    public function toQuery(bool $asJson = false): array|false|string
    {
        $query = $this->query->getRawPayload();

        return $asJson ? json_encode($query) : $query;
    }

    /**
     * @return mixed|null
     *
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function create(array $attributes)
    {
        $payload = [
            'index' => $this->resolveWriteIndex(),
        ];

        if (Arr::has($attributes, 'id')) {
            $payload['id'] = data_get($attributes, 'id');
            data_forget($attributes, 'id');
        }

        $payload['body'] = $attributes;

        $res = $this->query->getConnection()->index($payload);

        return data_get($res, '_id');
    }

    /**
     * Bulk-index a list of documents. A row's `id` (if present) becomes the `_id`.
     *
     * @param  array<int, array>  $rows
     */
    public function bulk(array $rows, ?int $chunkSize = null): BulkResult
    {
        return $this->performBulk($rows, 'index', $chunkSize);
    }

    /**
     * Bulk update-or-insert a list of documents. Each row must include an `id`.
     *
     * @param  array<int, array>  $rows
     */
    public function upsert(array $rows, ?int $chunkSize = null): BulkResult
    {
        return $this->performBulk($rows, 'upsert', $chunkSize);
    }

    /**
     * @param  array<int, array>  $rows
     *
     * @throws BulkLimitExceeded
     */
    protected function performBulk(array $rows, string $action, ?int $chunkSize): BulkResult
    {
        $max = (int) config('elasticbridge.bulk.max', 10000);

        if (count($rows) > $max) {
            throw BulkLimitExceeded::make(count($rows), $max);
        }

        $size = $chunkSize ?? (int) config('elasticbridge.bulk.chunk_size', 500);

        $index = $this->resolveWriteIndex();

        $items = [];

        foreach (array_chunk($rows, max($size, 1)) as $chunk) {
            $response = $this->query->getConnection()->bulk([
                'body' => $this->buildBulkBody($chunk, $action, $index),
            ]);

            $items = array_merge($items, data_get($response, 'items', []));
        }

        return new BulkResult($items);
    }

    /**
     * Build the NDJSON action/source body for a bulk request.
     *
     * @param  array<int, array>  $rows
     * @return array<int, array>
     */
    protected function buildBulkBody(array $rows, string $action, string $index): array
    {
        $body = [];

        foreach ($rows as $row) {
            if ($action === 'upsert') {
                $id = data_get($row, 'id');

                if ($id === null) {
                    throw new InvalidArgumentException('upsert requires an "id" for each document.');
                }

                data_forget($row, 'id');

                $body[] = ['update' => ['_index' => $index, '_id' => $id]];
                $body[] = ['doc' => $row, 'doc_as_upsert' => true];

                continue;
            }

            $meta = ['_index' => $index];

            if (Arr::has($row, 'id')) {
                $meta['_id'] = data_get($row, 'id');
                data_forget($row, 'id');
            }

            $body[] = ['index' => $meta];
            $body[] = $row;
        }

        return $body;
    }

    /**
     * increases the value of a field by the counter provided
     */
    public function increment(string $field, int $counter = 1): bool
    {
        return $this->scriptRequest(sprintf('ctx._source.%s += params.count', $field), ['count' => $counter]);
    }

    /**
     * decreases the value of a field by the counter provided
     *
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function decrement(string $field, int $counter = 1): bool
    {
        return $this->scriptRequest(sprintf('ctx._source.%s -= params.count', $field), ['count' => $counter]);
    }

    /**
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function save(): bool
    {
        $id = $this->bridge->id;

        // Run the existence check on an isolated builder so this builder's
        // query state (term, values) is not mutated by find().
        $res = $this->bridge->newBridgeQuery()->find($id);

        if (! $res) {
            $id = $this->create($this->bridge->attributesToArray());

            return boolval($id);
        }

        return $this->query->save($this->bridge, $this->resolveWriteIndex());
    }

    /**
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function scriptRequest(string $source, array $params): bool
    {
        return $this->query->update($this->resolveWriteIndex(), [
            'script' => [
                'source' => $source,
                'params' => $params,
            ],
        ], $this->bridge->id);
    }

    /**
     * @return mixed
     */
    public function getAggregateForASpecificQuery(string $type, string $field, $options = [])
    {
        $this->take(0);

        $this->query->setAggregate($this->getAggregateQuery($type, $field, $options));

        $results = $this->get();

        $marco = Str::camel(sprintf('%s_%s', $type, $field));

        return $results->$marco();
    }
}
