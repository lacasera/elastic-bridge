<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Feature;

use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

class RangeQueryTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Return an empty search response by default
        Room::fake([
            'hits' => [
                'total' => ['value' => 0, 'relation' => 'eq'],
                'hits' => [],
            ],
        ]);
    }

    #[Test]
    public function it_builds_a_single_field_range_query(): void
    {
        $query = Room::query()
            ->asRange()
            ->range('price', 'gte', 30)
            ->range('price', 'lte', 300)
            ->toQuery();

        $this->assertEquals([
            'query' => [
                'range' => [
                    'price' => [
                        'gte' => 30,
                        'lte' => 300,
                    ],
                ],
            ],
        ], $query);
    }

    #[Test]
    public function it_builds_multiple_field_ranges_as_bool_filters(): void
    {
        $query = Room::query()
            ->asRange()
            ->range('price', 'gte', 30)
            ->range('age', 'gte', 18)
            ->toQuery();

        $this->assertEquals([
            'query' => [
                'bool' => [
                    'filter' => [
                        ['range' => ['price' => ['gte' => 30]]],
                        ['range' => ['age' => ['gte' => 18]]],
                    ],
                ],
            ],
        ], $query);
    }

    #[Test]
    public function it_executes_single_field_range_query_without_error(): void
    {
        $results = Room::query()
            ->asRange()
            ->range('price', 'gte', 30)
            ->range('price', 'lte', 300)
            ->get();

        $this->assertSame(0, $results->count());
    }

    #[Test]
    public function it_executes_multi_field_range_filters_without_error(): void
    {
        $results = Room::query()
            ->asRange()
            ->range('price', 'gte', 30)
            ->range('age', 'gte', 18)
            ->get();

        $this->assertSame(0, $results->count());
    }
}
