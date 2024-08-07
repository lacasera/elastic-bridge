<?php

namespace Lacasera\ElasticBridge\Query;

use Lacasera\ElasticBridge\Connection\ElasticConnection;

class QueryBuilder
{
    protected array $payload = [
        'should' => [],
        'must' => [],
        'filter' => [],
    ];

    public function __construct(protected ElasticConnection $connection) {}

    public function getConnection()
    {
        return $this->connection;
    }

    public function set(string $key, array $payload)
    {
        $data = data_get($this->payload, $key);

        if (! $data) {
            $this->payload[$key] = [$payload];
        } else {
            array_push($data, $payload);
            data_set($this->payload, $key, $data);
        }
    }

    public function getPayload(): array
    {
        return [
            'query' => [
                'bool' => $this->payload,
            ],
        ];
    }
}
