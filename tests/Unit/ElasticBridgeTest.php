<?php

namespace Lacasera\ElasticBridge\Tests\Unit;

use Lacasera\ElasticBridge\Builder\BridgeBuilder;
use Lacasera\ElasticBridge\PaginatedCollection;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

class ElasticBridgeTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Room::fake($this->getFakeData());
    }

    #[Test]
    public function it_can_get_index_from_class_name(): void
    {
        $this->assertEquals('rooms', (new Room)->getIndex());
    }

    #[Test]
    public function it_should_return_a_new_bridge_builder_instance(): void
    {
        $room = new Room;

        $this->assertInstanceOf(BridgeBuilder::class, $room->newBridgeQuery());
    }

    #[Test]
    public function it_should_use_an_instance_of_paginating_collection_class_when_doing_simple_pagination(): void
    {
        $results = Room::asBoolean()->matchAll()->simplePaginate(4)->get();

        $this->assertInstanceOf(PaginatedCollection::class, $results);
    }

    #[Test]
    public function it_should_use_an_instance_of_paginating_collection_class_when_doing_cursor_pagination(): void
    {
        $results = Room::asBoolean()->matchAll()->cursorPaginate(4)->get();
        $this->assertInstanceOf(PaginatedCollection::class, $results);
    }

    #[Test]
    public function it_should_return_an_instance_of_query_builder_class(): void
    {
        $this->assertInstanceOf(BridgeBuilder::class, Room::query());
    }

    #[Test]
    public function it_serializes_to_json_with_only_source_data(): void
    {
        $room = new Room;
        $room->setRawAttributes([
            '_id' => '123',
            '_index' => 'rooms',
            '_score' => 1.5,
            '_source' => [
                'name' => 'Deluxe Room',
                'price' => 250,
                'code' => 'DLX',
            ],
        ]);

        $json = json_decode($room->toJson(), true);

        $this->assertEquals('Deluxe Room', $json['name']);
        $this->assertEquals(250, $json['price']);
        $this->assertEquals('DLX', $json['code']);
        $this->assertArrayNotHasKey('_id', $json);
        $this->assertArrayNotHasKey('_index', $json);
        $this->assertArrayNotHasKey('_score', $json);
    }

    #[Test]
    public function it_converts_to_array_with_only_source_data(): void
    {
        $room = new Room;
        $room->setRawAttributes([
            '_id' => '456',
            '_index' => 'rooms',
            '_score' => 2.0,
            '_source' => [
                'name' => 'Standard Room',
                'price' => 150,
            ],
        ]);

        $array = $room->toArray();

        $this->assertEquals('Standard Room', $array['name']);
        $this->assertEquals(150, $array['price']);
        $this->assertArrayNotHasKey('_id', $array);
        $this->assertArrayNotHasKey('_index', $array);
        $this->assertArrayNotHasKey('_score', $array);
    }

    #[Test]
    public function it_provides_raw_attributes_including_metadata(): void
    {
        $room = new Room;
        $room->setRawAttributes([
            '_id' => '789',
            '_index' => 'rooms',
            '_score' => 3.5,
            '_source' => [
                'name' => 'Suite',
                'price' => 500,
            ],
        ]);

        $raw = $room->getRawAttributes();

        $this->assertEquals('789', $raw['_id']);
        $this->assertEquals('rooms', $raw['_index']);
        $this->assertEquals(3.5, $raw['_score']);
        $this->assertEquals('Suite', $raw['_source']['name']);
        $this->assertEquals(500, $raw['_source']['price']);
    }

    #[Test]
    public function it_fetches_all_records_with_cursor_pagination(): void
    {
        $results = Room::all();

        $this->assertInstanceOf(PaginatedCollection::class, $results);
        $this->assertNotEmpty($results);
    }

    #[Test]
    public function it_fetches_all_records_with_custom_per_page(): void
    {
        $results = Room::all(10);

        $this->assertInstanceOf(PaginatedCollection::class, $results);
        $this->assertNotEmpty($results);
    }
}
