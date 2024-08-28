<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query;

use Illuminate\Support\Collection;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Exceptions\MissingTermLevelQuery;

class QueryBuilder
{
    const RAW_TERM_LEVEL = 'raw';

    public const PAGINATION_SIZE = 15;

    /**
     * @var array|array[]
     */
    protected array $payload = [];

    protected array $sort = [];

    protected array $filters = [];

    protected array $paginate = [];

    protected array $aggregates = [];

    protected ?string $term = null;

    protected string $type = 'query';

    public function __construct(public ConnectionInterface $connection) {}

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function get(string $index, $columns = ['*']): mixed
    {
        return $this->makeSearchRequest($index, $columns);
    }

    public function setRawPayload(array $query): void
    {
        $this->payload = $query;
    }

    public function setPayload(string $key, mixed $payload)
    {
        $data = data_get($this->payload, $key);

        if (! $data) {
            $this->payload[$key] = ! is_array($payload) ? [$payload] : $payload;
        } else {
            $data[] = $payload;
            data_set($this->payload, $key, $data);
        }

        return $this;
    }

    /**
     * @return void
     */
    public function setSort(array $query)
    {
        $this->sort[] = $query;
    }

    public function count(string $index)
    {
        $payload = $this->hasPayload() ? $this->getPayload() : $this->defaultPayload();

        return $this->getConnection()
            ->getClient()
            ->count([
                'index' => $index,
                'body' => $payload,
            ])->asArray()['count'];
    }

    /**
     * TODO refactor this method..
     *
     * @return array[]
     *
     * @throws MissingTermLevelQuery
     */
    public function getPayload($columns = ['*']): array
    {
        if (! $this->term) {
            throw new MissingTermLevelQuery('set term level query');
        }

        if ($this->term === self::RAW_TERM_LEVEL) {
            $body = $this->payload;
        } else {
            $body = [
                $this->term => $this->payload,
            ];
        }

        if ($this->filters) {
            $body[$this->term]['filter'] = $this->filters;
        }

        $payload[$this->type] = $body;

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

    public function setTerm(string $term)
    {
        $this->term = $term;

        return $this;
    }

    public function setAggregate(array $payload)
    {
        $this->aggregates = $payload;

        return $this;
    }

    /**
     * @return void
     */
    public function setFilter($type, $field, $value, $operator = null)
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

    /**
     * @return void
     */
    public function setRawFilters(array $payload)
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

    /**
     * @return void
     */
    public function setPagination(array $payload)
    {
        $this->paginate = $payload;
    }

    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    private function hasSort(): bool
    {
        return ! empty($this->sort);
    }

    /**
     * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    protected function makeSearchRequest(string $index, $columns = ['*']): mixed
    {
        return $this->makeRequest($index, $columns);
    }

    public function makeAggregateRequest(string $type, string $index)
    {
        return $this->setType('aggs')->makeRequest($index)['aggregations'][$type]['value'];
    }

    protected function makeRequest(string $index, $columns = ['*']): array
    {
        return $this->getConnection()
            ->getClient()
            ->search([
                'index' => $index,
                'body' => $this->getPayload($columns),
            ])->asArray();
    }

    private function isSelectingFields(Collection $columns): bool
    {
        return $columns->isNotEmpty() && ! $columns->contains('*');
    }

    private function isPaginating(): bool
    {
        return ! empty($this->paginate);
    }

    public function hasPayload()
    {
        return ! empty($this->payload);
    }

    private function defaultPayload()
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

    private function shouldAttachAggregate()
    {
        return ! empty($this->aggregates);
    }

    public function attachAggregateQuery(string $string, string $field) {}
}
