<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Mock\Client;
use Lacasera\ElasticBridge\Contracts\SearchConnectionInterface;
use Nyholm\Psr7\Response;
use Override;

class MockElasticConnection implements SearchConnectionInterface
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
    public function search(array $params): array
    {
        $response = $this->connection->search($params);

        return $response->asArray();
    }

    #[Override]
    public function index(array $params): array
    {
        $response = $this->connection->index($params);

        return $response->asArray();
    }

    #[Override]
    public function get(array $params): array
    {
        $response = $this->connection->get($params);

        return $response->asArray();
    }

    #[Override]
    public function delete(array $params): array
    {
        $response = $this->connection->delete($params);

        return $response->asArray();
    }

    #[Override]
    public function indexExists(string $index): bool
    {
        return $this->connection->indices()->exists(['index' => $index])->asBool();
    }

    #[Override]
    public function createIndex(string $index, array $body = []): array
    {
        $params = ['index' => $index];
        if (! empty($body)) {
            $params['body'] = $body;
        }

        $response = $this->connection->indices()->create($params);

        return $response->asArray();
    }

    #[Override]
    public function deleteIndex(string $index): array
    {
        $response = $this->connection->indices()->delete(['index' => $index]);

        return $response->asArray();
    }

    #[Override]
    public function info(): array
    {
        $response = $this->connection->info();

        return $response->asArray();
    }

    #[Override]
    public function bulk(array $params): array
    {
        $response = $this->connection->bulk($params);

        return $response->asArray();
    }

    #[Override]
    public function count(array $params): array
    {
        $response = $this->connection->count($params);

        return $response->asArray();
    }

    #[Override]
    public function update(array $params): array
    {
        $response = $this->connection->update($params);

        return $response->asArray();
    }

    #[Override]
    public function getClient(): \Elastic\Elasticsearch\Client
    {
        return $this->connection;
    }
}
