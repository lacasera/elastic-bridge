<?php

namespace Lacasera\ElasticBridge\Tests;

use Elastic\Elasticsearch\ClientInterface;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Mock\Client;
use Nyholm\Psr7\Response;

class MockElasticConnection implements ConnectionInterface
{
    /**
     * @var \Elastic\Elasticsearch\Client
     */
    protected $connection;

    /**
     * @param array $response
     * @param int $status
     * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
     */
    public function __construct(array $response, int $status = 200)
    {
        $mock = new Client();

        $this->connection = ClientBuilder::create()
            ->setHttpClient($mock)
            ->build();

        $response = new Response($status, [
            Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME,
            'Content-Type' => 'application/json'
        ], json_encode($response));

        $mock->addResponse($response);
    }

    /**
     * @return ClientInterface
     */
    #[\Override] public function getClient(): ClientInterface
    {
        return $this->connection;
    }
}
