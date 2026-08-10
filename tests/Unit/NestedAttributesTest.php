<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Illuminate\Support\Carbon;
use Lacasera\ElasticBridge\Tests\Fixtures\Product;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NestedAttributesTest extends TestCase
{
    private function product(array $source): Product
    {
        return (new Product)->setRawAttributes(['_source' => $source]);
    }

    #[Test]
    public function it_casts_nested_values_on_read(): void
    {
        $product = $this->product([
            'hotel' => [
                'name' => 'Grand',
                'location' => ['lat' => '12.5', 'lon' => '7.1'],
                'opened_at' => '2024-01-02T03:04:05+00:00',
            ],
        ]);

        $this->assertSame(12.5, $product->getAttribute('hotel.location.lat'));
        $this->assertInstanceOf(Carbon::class, $product->getAttribute('hotel.opened_at'));
        // sibling without a cast is returned as-is
        $this->assertSame('7.1', $product->getAttribute('hotel.location.lon'));
    }

    #[Test]
    public function it_casts_and_stores_nested_values_on_assign(): void
    {
        $product = new Product;

        $product->setAttribute('hotel.location.lat', '9.9');

        $source = $product->getRawAttributes()['_source'];

        $this->assertSame(9.9, data_get($source, 'hotel.location.lat')); // float, nested
    }

    #[Test]
    public function it_applies_a_nested_mutator_on_assign(): void
    {
        $product = new Product;

        $product->setAttribute('hotel.slug', 'Grand Hotel');

        $this->assertSame('grand hotel', data_get($product->getRawAttributes()['_source'], 'hotel.slug'));
    }

    #[Test]
    public function it_reads_a_nested_accessor(): void
    {
        $product = $this->product(['hotel' => ['name' => 'grand']]);

        $this->assertSame('GRAND', $product->getAttribute('hotel.badge'));
    }

    #[Test]
    public function it_serializes_nested_casts_and_accessors_preserving_siblings(): void
    {
        $product = $this->product([
            'sku' => 'ABC',
            'hotel' => [
                'name' => 'grand',
                'location' => ['lat' => '12.5', 'lon' => '7.1'],
                'opened_at' => '2024-01-02T03:04:05+00:00',
            ],
        ]);

        $array = $product->toArray();

        $this->assertSame(12.5, $array['hotel']['location']['lat']);          // nested cast
        $this->assertSame('7.1', $array['hotel']['location']['lon']);          // sibling preserved
        $this->assertSame('2024-01-02T03:04:05+00:00', $array['hotel']['opened_at']); // nested datetime
        $this->assertSame('GRAND', $array['hotel']['badge']);                 // appended nested accessor
        $this->assertSame('grand', $array['hotel']['name']);                  // untouched
    }

    #[Test]
    public function a_missing_nested_cast_key_does_not_break_serialization(): void
    {
        $product = $this->product(['sku' => 'ABC']); // no "hotel" stored

        $array = $product->toArray();

        $this->assertSame('ABC', $array['sku']);
        // nested casts for absent keys are skipped (no phantom location)
        $this->assertNull(data_get($array, 'hotel.location.lat'));
        // an appended nested accessor still runs (always appended)
        $this->assertSame('', data_get($array, 'hotel.badge'));
    }
}
