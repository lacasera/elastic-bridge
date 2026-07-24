<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Feature;

use Lacasera\ElasticBridge\Exceptions\InvalidQuery;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

class ValidationTest extends TestCase
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
    public function it_rejects_an_invalid_order_direction(): void
    {
        $this->expectException(InvalidQuery::class);

        Room::asBoolean()->matchAll()->orderBy('price', 'SIDEWAYS');
    }

    #[Test]
    public function it_accepts_valid_order_directions_case_insensitively(): void
    {
        $query = Room::asBoolean()->matchAll()->orderBy('price', 'DESC')->toQuery();

        $this->assertSame(['price' => ['order' => 'desc']], $query['sort'][0]);
    }

    #[Test]
    public function it_rejects_an_invalid_range_filter_operator(): void
    {
        $this->expectException(InvalidQuery::class);

        Room::asBoolean()->matchAll()->filterByRange('price', 20, 'around');
    }

    #[Test]
    public function it_rejects_an_invalid_range_query_operator(): void
    {
        $this->expectException(InvalidQuery::class);

        Room::asRange()->range('price', 'approximately', 20);
    }

    #[Test]
    public function it_allows_a_bool_query_that_only_has_a_filter_clause(): void
    {
        $query = Room::asBoolean()->filterByTerm('code', 'usd')->toQuery();

        $this->assertSame([['term' => ['code' => 'usd']]], $query['query']['bool']['filter']);
    }
}
