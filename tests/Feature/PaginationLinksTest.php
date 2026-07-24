<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Feature;

use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

class PaginationLinksTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Room::fake($this->getFakeData());
    }

    #[Test]
    public function paginated_collection_links_return_expected_sort_and_total(): void
    {
        $results = Room::asBoolean()->matchAll()->orderBy('price', 'ASC')->cursorPaginate(4)->get();

        $links = $results->links();

        $this->assertEquals($results->first()->getRawAttributes()['sort'], $links['previous']);
        $this->assertEquals($results->last()->getRawAttributes()['sort'], $links['next']);
        $this->assertEquals($results->first()->getMeta()['value'], $links['total']);
    }

    #[Test]
    public function paginated_collection_links_handle_empty_results(): void
    {
        Room::fake([
            'hits' => [
                'total' => ['value' => 0, 'relation' => 'eq'],
                'hits' => [],
            ],
        ]);

        $results = Room::asBoolean()->matchAll()->cursorPaginate(4)->get();

        $links = $results->links();

        $this->assertSame(0, $links['total']);
        $this->assertSame([], $links['previous']);
        $this->assertSame([], $links['next']);
    }
}
