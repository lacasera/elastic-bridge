<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Illuminate\Support\Collection;
use Lacasera\ElasticBridge\DTO\Bucket;
use Lacasera\ElasticBridge\DTO\Stats;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AggregationResultsTest extends TestCase
{
    #[Test]
    public function it_registers_macro_for_stats_and_returns_stats_instance(): void
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

        $this->assertTrue(Collection::hasMacro('priceStats'));
        $stats = $collection->priceStats();
        $this->assertInstanceOf(Stats::class, $stats);
        $this->assertSame(30.0, $stats->sum());
    }

    #[Test]
    public function it_registers_macro_for_histogram_and_returns_bucket_collection(): void
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
        $this->assertTrue(Collection::hasMacro('histogramPrice'));

        $buckets = $collection->histogramPrice();
        $this->assertInstanceOf(Collection::class, $buckets);
        $this->assertInstanceOf(Bucket::class, $buckets->first());
        $this->assertSame('10.0', $buckets->first()->key());
    }
}
