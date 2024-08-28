<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Tests\MockElasticConnection;

trait FakeBridge
{
    /**
     * @return void
     */
    public static function fake(array $response, int $status = 200)
    {
        app()->bind(ConnectionInterface::class, function () use ($response, $status) {
            return new MockElasticConnection($response, $status);
        });
    }
}
