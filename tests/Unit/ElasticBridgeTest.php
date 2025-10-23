<?php

namespace Lacasera\ElasticBridge\Tests\Unit;

use Lacasera\ElasticBridge\Builder\BridgeBuilder;
use Lacasera\ElasticBridge\PaginatedCollection;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Override;

class ElasticBridgeTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Room::fake($this->getFakeData());
    }

    /**
     * @test
     */
    public function it_can_get_index_from_class_name(): void
    {
        $this->assertEquals('rooms', (new Room)->getIndex());
    }

    /**
     * @test
     */
    public function it_should_return_a_new_bridge_builder_instance(): void
    {
        $room = new Room;

        $this->assertInstanceOf(BridgeBuilder::class, $room->newBridgeQuery());
    }

    /**
     * @test
     */
    public function it_should_use_an_instance_of_paginating_collection_class_when_doing_simple_pagination(): void
    {
        $results = Room::asBoolean()->matchAll()->simplePaginate(4)->get();

        $this->assertInstanceOf(PaginatedCollection::class, $results);
    }

    /**
     * @test
     */
    public function it_should_use_an_instance_of_paginating_collection_class_when_doing_cursor_pagination(): void
    {
        $results = Room::asBoolean()->matchAll()->cursorPaginate(4)->get();
        $this->assertInstanceOf(PaginatedCollection::class, $results);
    }

    /**
     * @test
     */
    public function it_should_return_an_instance_of_query_builder_class(): void
    {
        $this->assertInstanceOf(BridgeBuilder::class, Room::query());
    }
}
