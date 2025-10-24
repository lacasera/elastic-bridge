<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasAttributesTest extends TestCase
{
    #[Test]
    public function it_uses__id_as_id_when_missing_in_source(): void
    {
        $room = new Room;
        $room->setRawAttributes([
            '_id' => 'abc-123',
            '_score' => 1.23,
            '_source' => [
                'code' => 'xoxo',
            ],
        ], ['value' => 99]);

        $this->assertSame('abc-123', $room->id);
        $this->assertSame(1.23, $room->getScore());
        $this->assertSame(['value' => 99], $room->getMeta());
    }

    #[Test]
    public function it_uses_source_id_when__id_is_missing(): void
    {
        $room = new Room;
        $room->setRawAttributes([
            '_source' => [
                'id' => 'from-source',
                'code' => 'abcd',
            ],
        ]);

        $this->assertSame('from-source', $room->id);
    }

    #[Test]
    public function it_casts_array_attributes_to_objects(): void
    {
        $room = new Room;
        $room->setRawAttributes([
            '_source' => [
                'hotel' => [
                    'location' => [
                        'lat' => 1.11,
                        'lon' => 2.22,
                    ],
                ],
            ],
        ]);

        $this->assertIsObject($room->hotel);
        $this->assertIsObject($room->hotel->location);
        $this->assertSame(1.11, $room->hotel->location->lat);
        $this->assertSame(2.22, $room->hotel->location->lon);
    }
}
