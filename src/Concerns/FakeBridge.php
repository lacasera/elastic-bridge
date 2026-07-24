<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

use Http\Mock\Client as MockClient;
use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Testing\FakeConnection;
use RuntimeException;

trait FakeBridge
{
    public static function fake(array $response, int $status = 200): void
    {
        if (! class_exists(MockClient::class)) {
            throw new RuntimeException(
                'The php-http/mock-client package is required to use '.static::class.'::fake(). '.
                'Install it with: composer require --dev php-http/mock-client'
            );
        }

        app()->bind(ConnectionInterface::class, fn (): FakeConnection => new FakeConnection($response, $status));
    }
}
