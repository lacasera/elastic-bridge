<?php

namespace Lacasera\ElasticBridge\Tests\Unit;

use Lacasera\ElasticBridge\Builder\BridgeBuilder;
use Lacasera\ElasticBridge\CursorPaginatedCollection;
use Lacasera\ElasticBridge\SimplePaginatedCollection;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;

class ElasticBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Room::fake($this->getFakeData());
    }

    /**
     * @return void
     *
     * @test
     */
    public function it_can_get_index_from_class_name()
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

        $this->assertInstanceOf(SimplePaginatedCollection::class, $results);
    }

    /**
     * @test
     */
    public function it_should_use_an_instance_of_paginating_collection_class_when_doing_cursor_pagination(): void
    {
        $results = Room::asBoolean()->matchAll()->cursorPaginate(4)->get();
        $this->assertInstanceOf(CursorPaginatedCollection::class, $results);
    }

    /**
     * @return void
     *
     * @test
     */
    public function it_should_return_an_instance_of_query_builder_class()
    {
        $this->assertInstanceOf(BridgeBuilder::class, Room::query());
    }
}
