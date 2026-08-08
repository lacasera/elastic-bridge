<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Connection;

interface ConnectionInterface
{
    /**
     * Execute a search request and return the decoded response.
     */
    public function search(array $params): array;

    /**
     * Execute a count request and return the decoded response (read `['count']`).
     */
    public function count(array $params): array;

    /**
     * Index a document and return the decoded response (read `['_id']`, etc.).
     */
    public function index(array $params): array;

    /**
     * Update a document and return whether the request succeeded.
     */
    public function update(array $params): bool;
}
