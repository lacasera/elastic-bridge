<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Traits;

use Lacasera\ElasticBridge\Query\AggregationBuilder;

trait HasAggregations
{
    /**
     * Start building a date histogram aggregation
     */
    public function dateHistogram(string $name, string $field, string $interval): AggregationBuilder
    {
        return new AggregationBuilder($this, $name, [
            'date_histogram' => [
                'field' => $field,
                'calendar_interval' => $interval,
                'min_doc_count' => 1,
            ],
        ]);
    }

    /**
     * Start building a terms aggregation
     */
    public function termsAgg(string $name, string $field, int $size = 10): AggregationBuilder
    {
        return new AggregationBuilder($this, $name, [
            'terms' => [
                'field' => $field,
                'size' => $size,
            ],
        ]);
    }

    /**
     * Start building a histogram aggregation
     */
    public function histogramAgg(string $name, string $field, float $interval): AggregationBuilder
    {
        return new AggregationBuilder($this, $name, [
            'histogram' => [
                'field' => $field,
                'interval' => $interval,
                'min_doc_count' => 1,
            ],
        ]);
    }

    /**
     * Start building a range aggregation
     */
    public function rangeAgg(string $name, string $field, array $ranges): AggregationBuilder
    {
        return new AggregationBuilder($this, $name, [
            'range' => [
                'field' => $field,
                'ranges' => $ranges,
            ],
        ]);
    }

    /**
     * Add a metric aggregation (avg, sum, min, max, etc.)
     */
    public function metric(string $name, string $type, string $field): AggregationBuilder
    {
        return new AggregationBuilder($this, $name, [
            $type => [
                'field' => $field,
            ],
        ]);
    }

    /**
     * Add a custom aggregation
     */
    public function addAggregation(string $name, array $aggregation): AggregationBuilder
    {
        return new AggregationBuilder($this, $name, $aggregation);
    }
}
