<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Testing;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Mock\Client as MockClient;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Nyholm\Psr7\Response;
use Override;

class FakeConnection implements ConnectionInterface
{
    protected Client $connection;

    /**
     * @throws AuthenticationException
     */
    public function __construct(array $response, int $status = 200)
    {
        $client = new MockClient;

        $this->connection = ClientBuilder::create()
            ->setHttpClient($client)
            ->build();

        $client->addResponse(new Response($status, [
            Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME,
            'Content-Type' => 'application/json',
        ], json_encode($response)));
    }

    #[Override]
    public function getClient(): Client
    {
        return $this->connection;
    }
}
