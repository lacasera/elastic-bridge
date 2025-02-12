<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Mock\Client;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Nyholm\Psr7\Response;

class MockElasticConnection implements ConnectionInterface
{
    /**
     * @var \Elastic\Elasticsearch\Client
     */
    protected $connection;

    /**
     * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
     */
    public function __construct(array $response, int $status = 200)
    {
        $mock = new Client;

        $this->connection = ClientBuilder::create()
            ->setHttpClient($mock)
            ->build();

        $response = new Response($status, [
            Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME,
            'Content-Type' => 'application/json',
        ], json_encode($response));

        $mock->addResponse($response);
    }

    #[\Override]
    public function getClient(): \Elastic\Elasticsearch\Client
    {
        return $this->connection;
    }
}
