<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\DTO;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class BulkResult
{
    /**
     * @param  array<int, array>  $items  Merged `items` entries from bulk responses.
     */
    public function __construct(private readonly array $items = []) {}

    /**
     * Documents written without error.
     *
     * @return Collection<int, array>
     */
    public function successful(): Collection
    {
        return $this->entries()->reject(fn (array $entry): bool => $this->isFailed($entry))->values();
    }

    /**
     * Documents that failed, each carrying its `error` detail.
     *
     * @return Collection<int, array>
     */
    public function failed(): Collection
    {
        return $this->entries()->filter(fn (array $entry): bool => $this->isFailed($entry))->values();
    }

    /**
     * Number of documents written successfully.
     */
    public function count(): int
    {
        return $this->successful()->count();
    }

    /**
     * Total number of documents attempted.
     */
    public function total(): int
    {
        return count($this->items);
    }

    public function hasErrors(): bool
    {
        return $this->failed()->isNotEmpty();
    }

    /**
     * The raw merged `items` array.
     *
     * @return array<int, array>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Unwrap each action-keyed item (e.g. `['index' => [...]]`) to its inner metadata.
     *
     * @return Collection<int, array>
     */
    private function entries(): Collection
    {
        return collect($this->items)->map(fn (array $item): array => (array) Arr::first($item));
    }

    private function isFailed(array $entry): bool
    {
        return isset($entry['error']) || (int) ($entry['status'] ?? 200) >= 300;
    }
}
