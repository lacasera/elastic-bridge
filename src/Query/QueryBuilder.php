<?php

namespace Lacasera\ElasticBridge\Query;

use Lacasera\ElasticBridge\Connection\ElasticConnection;
use Lacasera\ElasticBridge\Exceptions\MissingTermLevelQueryException;

class QueryBuilder
{
    const RAW_TERM_LEVEL = 'raw';

    /**
     * @var array|array[]
     */
    protected array $payload = [];

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

    /**
     * @return array[]
     *
     * @throws MissingTermLevelQueryException
     */
    public function getPayload(): array
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

        return [
            'query' => $body,
        ];
    }

    public function setTerm(string $term): void
    {
        $this->term = $term;
    }

    /**
     * @return array[]
     */
    public function getRawPayload(): array
    {
        return ['query' => $this->payload];
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
            'body' => $this->getPayload(),
        ];

        return $this->getConnection()
            ->getClient()
            ->search($params)
            ->asArray()['hits']['hits'];
    }
}
