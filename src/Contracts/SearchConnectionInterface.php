<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Contracts;

interface SearchConnectionInterface
{
    /**
     * Execute a search query
     */
    public function search(array $params): array;

    /**
     * Index a document
     */
    public function index(array $params): array;

    /**
     * Get a document by ID
     */
    public function get(array $params): array;

    /**
     * Delete a document
     */
    public function delete(array $params): array;

    /**
     * Check if index exists
     */
    public function indexExists(string $index): bool;

    /**
     * Create an index
     */
    public function createIndex(string $index, array $body = []): array;

    /**
     * Delete an index
     */
    public function deleteIndex(string $index): array;

    /**
     * Get cluster info
     */
    public function info(): array;

    /**
     * Execute bulk operations
     */
    public function bulk(array $params): array;

    /**
     * Count documents
     */
    public function count(array $params): array;

    /**
     * Update a document
     */
    public function update(array $params): array;

    /**
     * Get the underlying client instance
     */
    public function getClient(): mixed;
}
