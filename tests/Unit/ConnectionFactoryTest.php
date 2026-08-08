<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use InvalidArgumentException;
use Lacasera\ElasticBridge\Connection\ConnectionFactory;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Connection\ElasticConnection;
use Lacasera\ElasticBridge\Connection\OpenSearchConnection;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConnectionFactoryTest extends TestCase
{
    #[Test]
    public function it_defaults_to_the_elasticsearch_driver(): void
    {
        $this->assertInstanceOf(ElasticConnection::class, ConnectionFactory::make([]));
    }

    #[Test]
    public function it_resolves_the_elasticsearch_driver(): void
    {
        $this->assertInstanceOf(ElasticConnection::class, ConnectionFactory::make(['driver' => 'elasticsearch']));
    }

    #[Test]
    public function it_resolves_the_opensearch_driver(): void
    {
        $this->assertInstanceOf(OpenSearchConnection::class, ConnectionFactory::make(['driver' => 'opensearch']));
    }

    #[Test]
    public function it_throws_on_an_unknown_driver(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ConnectionFactory::make(['driver' => 'solr']);
    }

    #[Test]
    public function the_container_resolves_the_connection_for_the_configured_driver(): void
    {
        config()->set('elasticbridge.driver', 'opensearch');

        $this->assertInstanceOf(OpenSearchConnection::class, app(ConnectionInterface::class));

        config()->set('elasticbridge.driver', 'elasticsearch');

        $this->assertInstanceOf(ElasticConnection::class, app(ConnectionInterface::class));
    }
}
