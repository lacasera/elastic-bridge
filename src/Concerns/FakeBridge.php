<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Tests\MockElasticConnection;

trait FakeBridge
{
    public static function fake(array $response, int $status = 200): void
    {
        app()->bind(ConnectionInterface::class, fn(): MockElasticConnection => new MockElasticConnection($response, $status));
    }
}
