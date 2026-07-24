<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge;

use Illuminate\Support\Collection as BaseCollection;
use Override;

class Collection extends BaseCollection
{
    /**
     * Aggregation results keyed by their camel-cased aggregation name.
     *
     * Stored per-instance (rather than as a global Collection macro) so that
     * multiple aggregations coexist on one response and nothing leaks across
     * requests in long-lived workers (Octane, queues).
     *
     * @var array<string, mixed>
     */
    protected array $aggregations = [];

    /**
     * @param  array<string, mixed>  $aggregations
     * @return $this
     */
    public function setAggregations(array $aggregations): static
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    /**
     * Resolve aggregation results by name (e.g. `$results->priceStats()`)
     * before falling back to registered collection macros.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    #[Override]
    public function __call($method, $parameters)
    {
        if (array_key_exists($method, $this->aggregations)) {
            return $this->aggregations[$method];
        }

        return parent::__call($method, $parameters);
    }
}
