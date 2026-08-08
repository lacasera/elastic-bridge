<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Lacasera\ElasticBridge\Testing\FakeConnection;

trait FakeBridge
{
    public static function fake(array $response, int $status = 200): FakeConnection
    {
        $fakeConnection = new FakeConnection($response, $status);

        app()->instance(ConnectionInterface::class, $fakeConnection);

        return $fakeConnection;
    }
}
