<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Testing;

use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Override;

class FakeConnection implements ConnectionInterface
{
    public function __construct(
        private readonly array $response,
        private readonly int $status = 200,
    ) {}

    #[Override]
    public function search(array $params): array
    {
        return $this->response;
    }

    #[Override]
    public function count(array $params): array
    {
        return $this->response;
    }

    #[Override]
    public function index(array $params): array
    {
        return $this->response;
    }

    #[Override]
    public function update(array $params): bool
    {
        return $this->status < 300;
    }
}
