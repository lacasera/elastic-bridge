<?php

namespace Lacasera\ElasticBridge\Query;

use Lacasera\ElasticBridge\Connection\ElasticConnection;

class QueryBuilder
{
    /**
     * @var array|array[]
     */
    protected array $payload = [
        'should' => [],
        'must' => [],
        'filter' => [],
    ];

    /**
     * @TODO  : Refactor this to use ConnectionInterface.
     * currently getting some binding resolution exception.
     * don't want to waste time debugging.
     * will fix when main feature are implemented and start testing
     */
    public function __construct(protected ElasticConnection $connection) {}

    /**
     * @return ElasticConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $index
     * @param $columns
     * @return mixed
     */
    public function get(string $index, $columns = ['*'])
    {
        return $this->makeRequest($index, $columns);
    }

    /**
     * @param string $key
     * @param $payload
     * @return void
     */
    public function setPayload(string $key,  $payload)
    {
        $data = data_get($this->payload, $key);

        if (!$data) {
            $this->payload[$key] = [$payload];
        } else {
            array_push($data, $payload);
            data_set($this->payload, $key, $data);
        }
    }

    /**
     * @return array[]
     */
    public function getPayload(): array
    {
        return [
            'query' => [
                'bool' => $this->payload,
            ],
        ];
    }

    /**
     * @param string $index
     * @return mixed
     * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    private function makeRequest(string $index)
    {
        return $this->getConnection()
            ->getClient()
            ->search([
                'index' => $index,
                'body' => $this->getPayload(),
            ])
            ->asArray()['hits']['hits'];
    }
}
