<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Mock\Client;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Nyholm\Psr7\Response;
use Override;

class MockElasticConnection implements ConnectionInterface
{
    protected \Elastic\Elasticsearch\Client $connection;

    /**
     * @throws AuthenticationException
     */
    public function __construct(array $response, int $status = 200)
    {
        $client = new Client;

        $this->connection = ClientBuilder::create()
            ->setHttpClient($client)
            ->build();

        $response = new Response($status, [
            Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME,
            'Content-Type' => 'application/json',
        ], json_encode($response));

        $client->addResponse($response);
    }

    #[Override]
    public function getClient(): \Elastic\Elasticsearch\Client
    {
        return $this->connection;
    }
}
