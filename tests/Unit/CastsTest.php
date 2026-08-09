<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Lacasera\ElasticBridge\ElasticBridge;
use Lacasera\ElasticBridge\Tests\Fixtures\Address;
use Lacasera\ElasticBridge\Tests\Fixtures\Currency;
use Lacasera\ElasticBridge\Tests\Fixtures\Product;
use Lacasera\ElasticBridge\Tests\Room;
use Lacasera\ElasticBridge\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use ValueError;

class CastsTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    }

    private function product(array $source): Product
    {
        return (new Product)->setRawAttributes(['_source' => $source]);
    }

    #[Test]
    public function it_casts_primitive_and_structured_values_on_read(): void
    {
        $product = $this->product([
            'in_stock' => 1,
            'quantity' => '5',
            'weight' => '1.5',
            'price' => '19.5',
            'sku' => 123,
            'tags' => ['a', 'b'],
            'meta' => ['x' => 1],
            'options' => [1, 2, 3],
        ]);

        $this->assertTrue($product->in_stock);
        $this->assertSame(5, $product->quantity);
        $this->assertSame(1.5, $product->weight);
        $this->assertSame('19.50', $product->price);
        $this->assertSame('123', $product->sku);
        $this->assertSame(['a', 'b'], $product->tags);
        $this->assertInstanceOf(stdClass::class, $product->meta);
        $this->assertSame(1, $product->meta->x);
        $this->assertInstanceOf(Collection::class, $product->options);
        $this->assertSame([1, 2, 3], $product->options->all());
    }

    #[Test]
    public function it_casts_dates_on_read(): void
    {
        $product = $this->product([
            'published_at' => '2024-01-02T03:04:05+00:00',
            'archived_at' => '2024-02-02T00:00:00+00:00',
            'released_on' => '2024-03-04T10:00:00+00:00',
            'seen_at' => '2024-01-01T00:00:00+00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $product->published_at);
        $this->assertInstanceOf(CarbonImmutable::class, $product->archived_at);
        $this->assertSame('2024-03-04', $product->released_on->format('Y-m-d'));
        $this->assertSame('00:00:00', $product->released_on->format('H:i:s'));
        $this->assertIsInt($product->seen_at);
    }

    #[Test]
    public function it_casts_enums_and_enum_collections_on_read(): void
    {
        $product = $this->product([
            'currency' => 'usd',
            'statuses' => json_encode(['usd', 'eur']),
        ]);

        $this->assertSame(Currency::USD, $product->currency);
        $this->assertInstanceOf(Collection::class, $product->statuses);
        $this->assertSame(Currency::USD, $product->statuses->first());
        $this->assertSame(Currency::EUR, $product->statuses->last());
    }

    #[Test]
    public function it_casts_encrypted_and_hashed_values(): void
    {
        $hash = Hash::make('pw');

        $product = $this->product([
            'secret' => Crypt::encrypt('top-secret', serialize: false),
            'secret_list' => Crypt::encrypt(json_encode(['a', 'b']), serialize: false),
            'password' => $hash,
        ]);

        $this->assertSame('top-secret', $product->secret);
        $this->assertSame(['a', 'b'], $product->secret_list);
        $this->assertSame($hash, $product->password); // hashed is read as-is
    }

    #[Test]
    public function it_resolves_a_custom_cast_value_object(): void
    {
        $product = $this->product([
            'address_line_one' => '1 A St',
            'address_line_two' => 'Apt 2',
        ]);

        $address = $product->address;

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame('1 A St', $address->lineOne);
        $this->assertSame('Apt 2', $address->lineTwo);
    }

    #[Test]
    public function it_casts_values_to_storable_form_on_write(): void
    {
        $product = new Product;

        $product->in_stock = 1;
        $product->currency = Currency::EUR;
        $product->published_at = '2024-01-02 03:04:05';
        $product->tags = ['x'];
        $product->secret = 'hi';
        $product->password = 'pw';
        $product->address = new Address('L1', 'L2');

        $source = $product->getRawAttributes()['_source'];

        $this->assertTrue($source['in_stock']);
        $this->assertSame('eur', $source['currency']);
        $this->assertStringContainsString('2024-01-02', $source['published_at']);
        $this->assertSame(['x'], $source['tags']);
        $this->assertSame('hi', Crypt::decrypt($source['secret'], unserialize: false));
        $this->assertTrue(Hash::check('pw', $source['password']));
        $this->assertSame('L1', $source['address_line_one']);
        $this->assertSame('L2', $source['address_line_two']);
    }

    #[Test]
    public function it_serializes_casts_in_to_array(): void
    {
        $product = $this->product([
            'sku' => 'ABC',
            'in_stock' => 1,
            'price' => '19.5',
            'currency' => 'usd',
            'published_at' => '2024-01-02T03:04:05+00:00',
        ]);

        $array = $product->toArray();

        $this->assertTrue($array['in_stock']);
        $this->assertSame('19.50', $array['price']); // decimal:2 serializes without throwing
        $this->assertSame('usd', $array['currency']);
        $this->assertSame('2024-01-02T03:04:05+00:00', $array['published_at']);
        $this->assertSame('Product: ABC', $array['display_name']); // appended accessor
    }

    #[Test]
    public function a_bridge_without_casts_serializes_source_unchanged(): void
    {
        $room = (new Room)->setRawAttributes(['_source' => ['a' => 1, 'b' => ['c' => 2]]]);

        $this->assertSame(['a' => 1, 'b' => ['c' => 2]], $room->toArray());
    }

    #[Test]
    public function an_invalid_enum_value_throws(): void
    {
        $this->expectException(ValueError::class);

        $this->product(['currency' => 'not-a-currency'])->currency;
    }

    #[Test]
    public function an_unknown_class_cast_throws(): void
    {
        $bridge = new class extends ElasticBridge
        {
            protected $casts = ['thing' => 'App\\Nope\\DoesNotExist'];
        };

        $bridge->setRawAttributes(['_source' => ['thing' => 'x']]);

        $this->expectException(InvalidArgumentException::class);

        $bridge->thing;
    }
}
