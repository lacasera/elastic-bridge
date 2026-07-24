<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Illuminate\Support\Collection as BaseCollection;
use Lacasera\ElasticBridge\Collection;
use Lacasera\ElasticBridge\DTO\Bucket;
use Lacasera\ElasticBridge\DTO\Stats;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AggregationResultsTest extends TestCase
{
    #[Test]
    public function it_resolves_stats_aggregation_on_the_collection_instance(): void
    {
        $items = [
            'hits' => [
                'total' => ['value' => 0, 'relation' => 'eq'],
                'hits' => [],
            ],
            'aggregations' => [
                'price_stats' => [
                    'count' => 2.0,
                    'min' => 10.0,
                    'max' => 20.0,
                    'avg' => 15.0,
                    'sum' => 30.0,
                ],
            ],
        ];

        $collection = (new Room)->hydrate($items);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertArrayHasKey('priceStats', $collection->getAggregations());

        $stats = $collection->priceStats();
        $this->assertInstanceOf(Stats::class, $stats);
        $this->assertSame(30.0, $stats->sum());
    }

    #[Test]
    public function it_resolves_histogram_aggregation_to_bucket_collection(): void
    {
        $items = [
            'hits' => [
                'total' => ['value' => 0, 'relation' => 'eq'],
                'hits' => [],
            ],
            'aggregations' => [
                'histogram_price' => [
                    'buckets' => [
                        ['key' => '10.0', 'doc_count' => 1, 'from' => 10.0, 'to' => 20.0],
                        ['key' => '20.0', 'doc_count' => 2, 'from' => 20.0, 'to' => 30.0],
                    ],
                ],
            ],
        ];

        $collection = (new Room)->hydrate($items);

        $buckets = $collection->histogramPrice();
        $this->assertInstanceOf(BaseCollection::class, $buckets);
        $this->assertInstanceOf(Bucket::class, $buckets->first());
        $this->assertSame('10.0', $buckets->first()->key());
    }

    #[Test]
    public function it_resolves_multiple_aggregations_from_a_single_response(): void
    {
        $items = [
            'hits' => [
                'total' => ['value' => 0, 'relation' => 'eq'],
                'hits' => [],
            ],
            'aggregations' => [
                'price_stats' => [
                    'count' => 1.0,
                    'min' => 5.0,
                    'max' => 5.0,
                    'avg' => 5.0,
                    'sum' => 5.0,
                ],
                'histogram_price' => [
                    'buckets' => [
                        ['key' => '0.0', 'doc_count' => 3, 'from' => 0.0, 'to' => 10.0],
                    ],
                ],
            ],
        ];

        $collection = (new Room)->hydrate($items);

        // Both aggregations must be retrievable — the old macro approach
        // registered only the first key.
        $this->assertInstanceOf(Stats::class, $collection->priceStats());
        $this->assertSame(5.0, $collection->priceStats()->sum());

        $buckets = $collection->histogramPrice();
        $this->assertInstanceOf(Bucket::class, $buckets->first());
        $this->assertSame(3.0, $buckets->first()->count());
    }

    #[Test]
    public function aggregations_do_not_leak_across_collection_instances(): void
    {
        $items = [
            'hits' => [
                'total' => ['value' => 0, 'relation' => 'eq'],
                'hits' => [],
            ],
            'aggregations' => [
                'price_stats' => [
                    'count' => 1.0,
                    'min' => 1.0,
                    'max' => 1.0,
                    'avg' => 1.0,
                    'sum' => 1.0,
                ],
            ],
        ];

        $withAgg = (new Room)->hydrate($items);
        $this->assertNotEmpty($withAgg->getAggregations());

        $withoutAgg = (new Room)->hydrate([
            'hits' => [
                'total' => ['value' => 0, 'relation' => 'eq'],
                'hits' => [],
            ],
        ]);

        $this->assertSame([], $withoutAgg->getAggregations());
    }
}
