<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Feature;

use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

class CountTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Room::fake(['count' => 20]);
    }

    #[Test]
    public function a_plain_count_matches_all_documents(): void
    {
        $fake = Room::fake(['count' => 20]);

        $this->assertSame(20, Room::count());

        $body = $fake->requests['count'][0]['body'];
        $this->assertArrayHasKey('match_all', $body['query']['bool']['should']);
    }

    #[Test]
    public function count_includes_filters_when_a_term_context_is_set(): void
    {
        $fake = Room::fake(['count' => 20]);

        Room::asBoolean()->filterByTerm('in_stock', true)->count();

        $body = $fake->requests['count'][0]['body'];

        // The filter must reach the count query — not be dropped for a match_all.
        $this->assertSame(
            [['term' => ['in_stock' => true]]],
            $body['query']['bool']['filter']
        );
        $this->assertArrayNotHasKey('should', $body['query']['bool']);
    }
}
