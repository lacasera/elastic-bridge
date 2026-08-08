<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Testing;

use Lacasera\ElasticBridge\Connection\ConnectionInterface;
use Override;

class FakeConnection implements ConnectionInterface
{
    /**
     * Params recorded for each operation, keyed by method name.
     *
     * @var array<string, array<int, array>>
     */
    public array $requests = [];

    public function __construct(
        private readonly array $response,
        private readonly int $status = 200,
    ) {}

    #[Override]
    public function search(array $params): array
    {
        return $this->record('search', $params, $this->response);
    }

    #[Override]
    public function count(array $params): array
    {
        return $this->record('count', $params, $this->response);
    }

    #[Override]
    public function index(array $params): array
    {
        return $this->record('index', $params, $this->response);
    }

    #[Override]
    public function update(array $params): bool
    {
        return $this->record('update', $params, $this->status < 300);
    }

    #[Override]
    public function bulk(array $params): array
    {
        return $this->record('bulk', $params, $this->response);
    }

    /**
     * Record a call's params and return the given result.
     *
     * @template T
     *
     * @param  T  $result
     * @return T
     */
    private function record(string $method, array $params, mixed $result): mixed
    {
        $this->requests[$method][] = $params;

        return $result;
    }
}
