<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AggregationBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock aggregation response for date histogram
        Room::fake([
            'hits' => [
                'total' => ['value' => 0, 'relation' => 'eq'],
                'hits' => [],
            ],
            'aggregations' => [
                'price_over_time' => [
                    'buckets' => [
                        [
                            'key_as_string' => '2023-01-01T00:00:00.000Z',
                            'key' => 1672531200000,
                            'doc_count' => 10,
                            'avg_price' => [
                                'value' => 250.5,
                            ],
                        ],
                        [
                            'key_as_string' => '2023-01-02T00:00:00.000Z',
                            'key' => 1672617600000,
                            'doc_count' => 15,
                            'avg_price' => [
                                'value' => 275.8,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function it_builds_date_histogram_aggregation_with_fluent_api(): void
    {
        $query = Room::query()
            ->asBoolean()
            ->filterByTerm('symbol', 'GOLD')
            ->filterByRange('timestamp', '2023-01-01', 'gte')
            ->size(0)
            ->dateHistogram('price_over_time', 'timestamp', '1d')
            ->avg('avg_price', 'price_eur')
            ->end()
            ->toQuery();

        $expected = [
            'size' => 0,
            'query' => [
                'bool' => [
                    'filter' => [
                        ['term' => ['symbol' => 'GOLD']],
                        ['range' => ['timestamp' => ['gte' => '2023-01-01']]],
                    ],
                ],
            ],
            'aggs' => [
                'price_over_time' => [
                    'date_histogram' => [
                        'field' => 'timestamp',
                        'calendar_interval' => '1d',
                        'min_doc_count' => 1,
                    ],
                    'aggs' => [
                        'avg_price' => [
                            'avg' => [
                                'field' => 'price_eur',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_executes_date_histogram_aggregation_and_accesses_results(): void
    {
        $results = Room::query()
            ->asBoolean()
            ->filterByTerm('symbol', 'GOLD')
            ->size(0)
            ->dateHistogram('price_over_time', 'timestamp', '1d')
            ->avg('avg_price', 'price_eur')
            ->end()
            ->get();

        // Test that we can access the aggregation results
        $priceOverTime = $results->priceOverTime();

        $this->assertIsArray($priceOverTime);
        $this->assertArrayHasKey('buckets', $priceOverTime);
        $this->assertCount(2, $priceOverTime['buckets']);

        // Test first bucket
        $firstBucket = $priceOverTime['buckets'][0];
        $this->assertEquals('2023-01-01T00:00:00.000Z', $firstBucket['key_as_string']);
        $this->assertEquals(10, $firstBucket['doc_count']);
        $this->assertEquals(250.5, $firstBucket['avg_price']['value']);

        // Test second bucket
        $secondBucket = $priceOverTime['buckets'][1];
        $this->assertEquals('2023-01-02T00:00:00.000Z', $secondBucket['key_as_string']);
        $this->assertEquals(15, $secondBucket['doc_count']);
        $this->assertEquals(275.8, $secondBucket['avg_price']['value']);
    }

    #[Test]
    public function it_builds_terms_aggregation_with_sub_aggregations(): void
    {
        $query = Room::query()
            ->asBoolean()
            ->size(0)
            ->termsAgg('symbols', 'symbol', 5)
            ->avg('avg_price', 'price_eur')
            ->sum('total_volume', 'volume')
            ->end()
            ->toQuery();

        $expected = [
            'size' => 0,
            'query' => [
                'bool' => [],
            ],
            'aggs' => [
                'symbols' => [
                    'terms' => [
                        'field' => 'symbol',
                        'size' => 5,
                    ],
                    'aggs' => [
                        'avg_price' => [
                            'avg' => [
                                'field' => 'price_eur',
                            ],
                        ],
                        'total_volume' => [
                            'sum' => [
                                'field' => 'volume',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $query);
    }
}
