<?php

namespace Lacasera\ElasticBridge\Query;

use Illuminate\Support\Collection;
use Lacasera\ElasticBridge\Connection\ElasticConnection;
use Lacasera\ElasticBridge\Exceptions\MissingTermLevelQueryException;

class QueryBuilder
{
    const RAW_TERM_LEVEL = 'raw';

    /**
     * @var array|array[]
     */
    protected array $payload = [];

    protected array $sort = [];

    protected array $filters = [];

    protected ?string $term = null;

    /**
     * @TODO  : Refactor this to use ConnectionInterface.
     * currently getting some binding resolution exception.
     * don't want to waste time debugging.
     * will fix when main feature are implemented and start testing
     */
    public function __construct(protected ElasticConnection $connection) {}

    public function getConnection(): ElasticConnection
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
        return $this->makeRequest($index, $columns);
    }

    public function setRawPayload(array $query): void
    {
        $this->payload = $query;
    }

    public function setPayload(string $key, mixed $payload): void
    {
        $data = data_get($this->payload, $key);

        if (! $data) {
            $this->payload[$key] = ! is_array($payload) ? [$payload] : $payload;
        } else {
            $data[] = $payload;
            data_set($this->payload, $key, $data);
        }
    }

    public function setSort(array $query)
    {
        $this->sort[] = $query;
    }

    /**
     * @return array[]
     *
     * @throws MissingTermLevelQueryException
     */
    public function getPayload($columns = ['*']): array
    {
        if (! $this->term) {
            throw new MissingTermLevelQueryException('set `term level` query');
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

        $payload ['query'] = $body;

        if ($this->hasSort()) {
            $payload['sort'] = $this->sort;
        }

        if ($this->isSelectingFields(collect($columns))) {
            $payload['_source'] = $columns;
        }

        return $payload;
    }

    /**
     * @param string $term
     * @return void
     */
    public function setTerm(string $term): void
    {
        $this->term = $term;
    }

    /**
     * @param $type
     * @param $field
     * @param $value
     * @param $operator
     * @return void
     */
    public function setFilter($type, $field, $value, $operator = null)
    {
        if ($type === 'term') {
            $this->filters[] = [
                'term' => [$field => $value]
            ];
        }

        if ($type === 'range') {
            $this->filters[] = [
                'range' => [
                    $field => [
                        $operator => $value
                    ]
                ]
            ];
        }
    }

    /**
     * @return array[]
     */
    public function getRawPayload(): array
    {
        return ['query' => $this->payload];
    }


    /**
     * @return bool
     */
    private function hasSort(): bool
    {
        return !empty($this->sort);
    }

    /**
     * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    private function makeRequest(string $index, $columns = ['*']): mixed
    {
        $params = [
            'index' => $index,
            'body' => $this->getPayload($columns),
        ];

        return $this->getConnection()
            ->getClient()
            ->search($params)
            ->asArray()['hits']['hits'];
    }

    private function isSelectingFields(Collection $columns)
    {
        return $columns->isNotEmpty() && !$columns->contains('*');
    }
}
