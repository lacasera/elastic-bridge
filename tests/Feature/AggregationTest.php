<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Feature;

use Illuminate\Support\Collection;
use Lacasera\ElasticBridge\DTO\Bucket;
use Lacasera\ElasticBridge\DTO\Stats;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AggregationTest extends TestCase
{
    private function emptyHits(): array
    {
        return ['hits' => ['total' => ['value' => 0, 'relation' => 'eq'], 'hits' => []]];
    }

    #[Test]
    public function standalone_metric_aggregate_returns_a_scalar(): void
    {
        Room::fake(['aggregations' => ['price_avg' => ['value' => 10.5]]]);

        $this->assertSame(10.5, Room::avg('price'));
    }

    #[Test]
    public function standalone_stats_returns_a_stats_dto(): void
    {
        Room::fake(['aggregations' => ['price_stats' => [
            'count' => 2, 'min' => 1.0, 'max' => 3.0, 'avg' => 2.0, 'sum' => 4.0,
        ]]]);

        $stats = Room::stats('price');

        $this->assertInstanceOf(Stats::class, $stats);
        $this->assertSame(3.0, $stats->max());
        $this->assertSame(4.0, $stats->sum());
    }

    #[Test]
    public function standalone_histogram_returns_a_bucket_collection(): void
    {
        Room::fake(['aggregations' => ['price_histogram' => ['buckets' => [
            ['key' => 0.0, 'doc_count' => 2],
            ['key' => 100.0, 'doc_count' => 3],
        ]]]]);

        $buckets = Room::histogram('price', 100);

        $this->assertInstanceOf(Collection::class, $buckets);
        $this->assertInstanceOf(Bucket::class, $buckets->first());
        $this->assertSame(2.0, $buckets->first()->count());
        $this->assertSame(0.0, $buckets->first()->key());   // numeric histogram keys are floats
        $this->assertSame(100.0, $buckets->last()->key());
    }

    #[Test]
    public function query_scoped_metric_aggregate_returns_a_scalar(): void
    {
        Room::fake($this->emptyHits() + ['aggregations' => ['price_avg' => ['value' => 7.5]]]);

        $avg = Room::asBoolean()->mustMatch('category', 'books')->avg('price');

        $this->assertSame(7.5, $avg);
    }

    #[Test]
    public function with_aggregate_range_reads_bucket_collection(): void
    {
        Room::fake($this->emptyHits() + ['aggregations' => ['price_range' => ['buckets' => [
            ['key' => '*-50.0', 'to' => 50, 'doc_count' => 4],
            ['key' => '50.0-*', 'from' => 50, 'doc_count' => 8],
        ]]]]);

        $ranges = Room::asBoolean()->matchAll()->take(0)
            ->withAggregate('range', 'price', ['ranges' => [['to' => 50], ['from' => 50]]])
            ->get()
            ->priceRange();

        $this->assertInstanceOf(Collection::class, $ranges);
        $this->assertInstanceOf(Bucket::class, $ranges->first());
        $this->assertSame(4.0, $ranges->first()->count());
    }
}
