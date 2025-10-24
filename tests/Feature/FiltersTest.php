<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Feature;

use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

class FiltersTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Room::fake([
            'hits' => [
                'total' => ['value' => 0, 'relation' => 'eq'],
                'hits' => [],
            ],
        ]);
    }

    #[Test]
    public function it_builds_a_term_filter(): void
    {
        $query = Room::asBoolean()
            ->filterByTerm('code', 'usd')
            ->toQuery();

        $this->assertEquals([
            'query' => [
                'bool' => [
                    'filter' => [
                        ['term' => ['code' => 'usd']],
                    ],
                ],
            ],
        ], $query);
    }

    #[Test]
    public function it_builds_a_geo_bounding_box_filter(): void
    {
        $query = Room::asBoolean()
            ->filterByGeoBoundingBox('hotel.location', ['lat' => 10, 'lon' => 10], ['lat' => 0, 'lon' => 0])
            ->toQuery();

        $this->assertEquals([
            'query' => [
                'bool' => [
                    'filter' => [
                        [
                            'geo_bounding_box' => [
                                'hotel.location' => [
                                    'top_left' => ['lat' => 10, 'lon' => 10],
                                    'bottom_right' => ['lat' => 0, 'lon' => 0],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $query);
    }
}
