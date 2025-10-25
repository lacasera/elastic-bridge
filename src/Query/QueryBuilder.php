<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query;

use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Exceptions\MissingTermLevelQuery;

class QueryBuilder
{
    public const RAW_TERM_LEVEL = 'raw';

    public const PAGINATION_SIZE = 15;

    /**
     * @var array|array[]
     */
    protected array $payload = [];

    protected array $sort = [];

    protected array $filters = [];

    protected array $paginate = [];

    protected array $aggregates = [];

    protected array $range = [];

    protected ?string $term = null;

    protected string $type = 'query';

    public function __construct(public ConnectionInterface $connection) {}

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function get(string $index, $columns = ['*']): mixed
    {
        return $this->makeSearchRequest($index, $columns);
    }

    public function setRawPayload(array $query): void
    {
        $this->payload = $query;
    }

    /**
     * @return $this
     */
    public function setPayload(string $key, mixed $payload): static
    {
        $data = data_get($this->payload, $key);

        if (! $data) {
            $this->payload[$key] = is_array($payload) ? $payload : [$payload];
        } else {
            $data[] = $payload;
            data_set($this->payload, $key, $data);
        }

        return $this;
    }

    public function setSort(array $query): void
    {
        $this->sort[] = $query;
    }

    /**
     * @return mixed
     *
     * @throws MissingTermLevelQuery
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function count(string $index)
    {
        $payload = $this->hasPayload() ? $this->getPayload() : $this->defaultPayload();

        return $this->getConnection()
            ->getClient()
            ->count([
                'index' => $index,
                'body' => $payload,
            ])
            ->asArray()['count'];
    }

    /**
     * Build the query payload deterministically.
     *
     *
     * @throws MissingTermLevelQuery
     */
    public function getPayload($columns = ['*']): array
    {
        if ($this->term === null) {
            throw new MissingTermLevelQuery('set term level query');
        }

        if ($this->term === self::RAW_TERM_LEVEL) {
            $body = $this->payload;
        } elseif ($this->term === 'bool') {
            $body = ['bool' => $this->payload];
        } else {
            $body = [$this->term => $this->payload];
        }

        $filters = $this->filters;

        // Normalize range entries collected via asRange()->range(...)
        $rangeEntries = $this->range;

        // If the term is specifically 'range', build a valid range query.
        if ($this->term === 'range' && $rangeEntries !== []) {
            if (count($rangeEntries) === 1) {
                // Single field range: { query: { range: { field: { ops } } } }
                $body = ['range' => $rangeEntries];
            } else {
                // Multiple fields require bool/filter of separate range clauses.
                $body = ['bool' => []];
                foreach ($rangeEntries as $field => $conditions) {
                    $filters[] = ['range' => [$field => $conditions]];
                }
            }
        }

        // Attach range filters and any other filters to bool container when present
        if ($filters !== [] && array_key_exists('bool', $body)) {
            $body['bool']['filter'] = $filters;
        }

        $payload = [$this->type => $body];

        if ($this->hasSort()) {
            $payload['sort'] = $this->sort;
        }

        if ($this->shouldAttachAggregate()) {
            $payload['aggs'] = $this->aggregates;
        }

        if ($this->isPaginating()) {
            $payload = array_merge($payload, $this->paginate);
        }

        if ($this->isSelectingFields(collect($columns))) {
            $payload['_source'] = $columns;
        }

        return $payload;
    }

    /**
     * @return $this
     */
    public function setTerm(string $term): static
    {
        $this->term = $term;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAggregate(array $payload): self
    {
        $this->aggregates = $payload;

        return $this;
    }

    public function setFilter($type, $field, $value, $operator = null): void
    {
        if ($type === 'term') {
            $this->filters[] = [
                'term' => [$field => $value],
            ];
        }

        if ($type === 'range') {
            $this->filters[] = [
                'range' => [
                    $field => [
                        $operator => $value,
                    ],
                ],
            ];
        }
    }

    public function setRawFilters(array $payload): void
    {
        $this->filters[] = $payload;
    }

    /**
     * @return array[]
     */
    public function getRawPayload(): array
    {
        return $this->getPayload();
    }

    public function setPagination(array $payload): void
    {
        $this->paginate = $payload;
    }

    /**
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    private function hasSort(): bool
    {
        return $this->sort !== [];
    }

    /**
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function makeSearchRequest(string $index, $columns = ['*']): mixed
    {
        return $this->makeRequest($index, $columns);
    }

    /**
     * @return mixed
     *
     * @throws MissingTermLevelQuery
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function makeAggregateRequest(string $type, string $index)
    {
        $complexAggregates = ['stats', 'histogram', 'range'];

        $results = $this->setType('aggs')->makeRequest($index)['aggregations'][$type];

        $type = Arr::first(explode('_', $type));

        if (! in_array($type, $complexAggregates)) {
            return $results['value'];
        }

        return $results;
    }

    /**
     * @throws MissingTermLevelQuery
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function makeRequest(string $index, $columns = ['*']): array
    {
        return $this->getConnection()
            ->getClient()
            ->search([
                'index' => $index,
                'body' => $this->getPayload($columns),
            ])->asArray();
    }

    /**
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function save(ElasticBridge $elasticBridge): bool
    {
        return $this->update($elasticBridge->getIndex(), [
            'doc' => data_get($elasticBridge->attributesToArray(), '_source'),
        ], $elasticBridge->id);
    }

    public function hasPayload(): bool
    {
        return $this->payload !== [];
    }

    /**
     * @return $this
     */
    public function range(string $field, string $operator, $value): static
    {
        // Collect range constraints by field. Supports chaining to merge ops.
        $existing = $this->range[$field] ?? [];
        $this->range[$field] = array_merge($existing, [$operator => $value]);

        return $this;
    }

    /**
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function update(string $index, array $body, $id): bool
    {
        $query = [
            'index' => $index,
            'id' => $id,
            'body' => $body,
        ];

        return $this->getConnection()->getClient()->update($query)->asBool();
    }

    /**
     * @return array
     *
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function searchRequest(array $body)
    {
        return $this->getConnection()
            ->getClient()
            ->search($body)
            ->asArray();
    }

    public function indexRequest(array $body, bool $asArray = true)
    {
        $result = $this->getConnection()
            ->getClient()
            ->index($body);

        return $asArray ? $result->asArray() : $result->asBool();
    }

    private function isSelectingFields(Collection $columns): bool
    {
        return $columns->isNotEmpty() && ! $columns->contains('*');
    }

    private function isPaginating(): bool
    {
        return $this->paginate !== [];
    }

    /**
     * @return array[]
     */
    private function defaultPayload(): array
    {
        return [
            'query' => [
                'bool' => [
                    'should' => [
                        'match_all' => [
                            'boost' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function shouldAttachAggregate(): bool
    {
        return $this->aggregates !== [];
    }
}
