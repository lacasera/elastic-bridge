<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Exceptions\MissingTermLevelQuery;

class QueryBuilder
{
    const RAW_TERM_LEVEL = 'raw';

    public const PAGINATION_SIZE = 15;

    protected array $payload = [];

    protected array $sort = [];

    protected array $filters = [];

    protected array $paginate = [];

    protected array $aggregates = [];

    protected array $range = [];

    protected ?string $term = null;

    protected string $type = 'query';

    public function __construct(public ConnectionInterface $connection) {}

    /**
     * @return ConnectionInterface
     */
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

    /**
     * @param array $query
     * @return void
     */
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
            $this->payload[$key] = ! is_array($payload) ? [$payload] : $payload;
        } else {
            $data[] = $payload;
            data_set($this->payload, $key, $data);
        }

        return $this;
    }

    /**
     * @param array $query
     * @return void
     */
    public function setSort(array $query)
    {
        $this->sort[] = $query;
    }

    /**
     * @return mixed
     *
     * @throws MissingTermLevelQuery
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
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
     *
     * @return array[]
     * @throws MissingTermLevelQuery
     */
    public function getPayload($columns = ['*']): array
    {
        if (!$this->term) {
            throw new MissingTermLevelQuery('set term level query');
        }

        $body = $this->term === self::RAW_TERM_LEVEL ? $this->payload : [$this->term => $this->payload];

        if ($this->filters) {
            $body[$this->term]['filter'] = $this->filters;
        }

        $payload = [$this->type => $body];

        $this->attachOptionalParameters($payload, $columns);


     //   dd(json_encode($payload));
        return $payload;
    }

    /**
     * @return $this
     */
    public function setTerm(string $term)
    {
        $this->term = $term;

        return $this;
    }

    /**
     * @param array $payload
     * @return $this
     */
    public function setAggregate(array $payload): self
    {
        $this->aggregates = $payload;

        return $this;
    }

    /**
     * @param $type
     * @param $field
     * @param $value
     * @param $operator
     * @return void
     */
    protected function setFilter($type, $field, $value, $operator = null)
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
     * @param array $payload
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
     * @param array $payload
     * @return void
     */
    public function setPagination(array $payload)
    {
        $this->paginate = $payload;
    }

    /**
     * @param string $type
     * @return $this
     */
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

    /**
     * @return mixed
     *
     * @throws MissingTermLevelQuery
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
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
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function makeRequest(string $index, $columns = ['*']): array
    {
        $response =  $this->getConnection()
            ->getClient()
            ->search([
                'index' => $index,
                'body' => $this->getPayload($columns),
            ])->asArray();

        if ($this->isPaginating()) {
            return array_merge($response, ['pagination' => $this->paginate]);
        }

        return $response;
    }

    /**
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function save(ElasticBridge $bridge): bool
    {
        return $this->update($bridge->getIndex(), [
            'doc' => data_get($bridge->attributesToArray(), '_source'),
        ], $bridge->id);
    }

    /**
     * @return bool
     */
    public function hasPayload(): bool
    {
        return ! empty($this->payload);
    }

    /**
     * @return $this
     */
    public function range(string $field, string $operator, $value)
    {
        $existing = data_get($this->hasPayload() ? $this->filters : $this->range, 'range.'.$field);

        if ($existing) {

            $payload = array_merge($existing["$field"], [$operator => $value]);
        } else {
            $payload = [
                "$field" => [
                    $operator => $value,
                ],
            ];
        }

        if ($this->hasPayload()) {
            data_set($this->filters, 'range', $payload);
        } else {
            data_set($this->range, 'range.'.$field, $payload);
        }

        return $this;
    }

    /**
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
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
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
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

    /**
     * Attach optional parameters to the payload.
     */
    protected function attachOptionalParameters(array &$payload, array $columns): void
    {
        if($this->hasSort()) {
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

        if ($this->range) {
            $payload['query'] = $this->range;
        }
    }

    private function isSelectingFields(Collection $columns): bool
    {
        return $columns->isNotEmpty() && ! $columns->contains('*');
    }

    private function isPaginating(): bool
    {
        return ! empty($this->paginate);
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

    /**
     * @return bool
     */
    private function shouldAttachAggregate(): bool
    {
        return ! empty($this->aggregates);
    }
}
