<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query;

use Lacasera\ElasticBridge\Builder\BridgeBuilder;

class AggregationBuilder
{
    private array $aggregation;

    private string $name;

    private BridgeBuilder $builder;

    public function __construct(BridgeBuilder $builder, string $name, array $aggregation)
    {
        $this->builder = $builder;
        $this->name = $name;
        $this->aggregation = $aggregation;
    }

    /**
     * Add options to the current aggregation
     */
    public function options(array $options): self
    {
        $mainAggType = array_key_first($this->aggregation);
        $this->aggregation[$mainAggType] = array_merge($this->aggregation[$mainAggType], $options);

        return $this;
    }

    /**
     * Set minimum document count
     */
    public function minDocCount(int $count): self
    {
        return $this->options(['min_doc_count' => $count]);
    }

    /**
     * Set missing value
     */
    public function missing($value): self
    {
        return $this->options(['missing' => $value]);
    }

    /**
     * Set size for terms aggregation
     */
    public function size(int $size): self
    {
        return $this->options(['size' => $size]);
    }

    /**
     * Add a sub-aggregation
     */
    public function subAgg(string $name, string $type, string $field, array $options = []): self
    {
        if (! isset($this->aggregation['aggs'])) {
            $this->aggregation['aggs'] = [];
        }

        $this->aggregation['aggs'][$name] = [
            $type => array_merge(['field' => $field], $options),
        ];

        return $this;
    }

    /**
     * Add an average sub-aggregation
     */
    public function avg(string $name, string $field): self
    {
        return $this->subAgg($name, 'avg', $field);
    }

    /**
     * Add a sum sub-aggregation
     */
    public function sum(string $name, string $field): self
    {
        return $this->subAgg($name, 'sum', $field);
    }

    /**
     * Add a min sub-aggregation
     */
    public function min(string $name, string $field): self
    {
        return $this->subAgg($name, 'min', $field);
    }

    /**
     * Add a max sub-aggregation
     */
    public function max(string $name, string $field): self
    {
        return $this->subAgg($name, 'max', $field);
    }

    /**
     * Add a count (value_count) sub-aggregation
     */
    public function count(string $name, string $field): self
    {
        return $this->subAgg($name, 'value_count', $field);
    }

    /**
     * Add a stats sub-aggregation
     */
    public function stats(string $name, string $field): self
    {
        return $this->subAgg($name, 'stats', $field);
    }

    /**
     * Add a terms sub-aggregation
     */
    public function terms(string $name, string $field, int $size = 10): self
    {
        return $this->subAgg($name, 'terms', $field, ['size' => $size]);
    }

    /**
     * Add a date histogram sub-aggregation
     */
    public function dateHistogram(string $name, string $field, string $interval): self
    {
        return $this->subAgg($name, 'date_histogram', $field, [
            'calendar_interval' => $interval,
            'min_doc_count' => 1,
        ]);
    }

    /**
     * Add a custom sub-aggregation
     */
    public function customSubAgg(string $name, array $aggregation): self
    {
        if (! isset($this->aggregation['aggs'])) {
            $this->aggregation['aggs'] = [];
        }

        $this->aggregation['aggs'][$name] = $aggregation;

        return $this;
    }

    /**
     * Finish building this aggregation and return to the main builder
     */
    public function end(): BridgeBuilder
    {
        // Add the aggregation to the query builder
        $this->builder->getQueryBuilder()->addAggregation($this->name, $this->aggregation);

        return $this->builder;
    }
}
