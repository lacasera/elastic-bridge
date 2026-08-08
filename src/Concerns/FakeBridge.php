<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Testing\FakeConnection;

trait FakeBridge
{
    public static function fake(array $response, int $status = 200): void
    {
        app()->bind(ConnectionInterface::class, fn (): FakeConnection => new FakeConnection($response, $status));
    }
}
