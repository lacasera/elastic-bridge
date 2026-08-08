<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Lacasera\ElasticBridge\Tests\Fixtures\Product;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AttributeMutatorTest extends TestCase
{
    #[Test]
    public function an_accessor_transforms_the_value_on_read(): void
    {
        $product = (new Product)->setRawAttributes(['_source' => ['name' => 'john']]);

        $this->assertSame('John', $product->name);
    }

    #[Test]
    public function a_mutator_transforms_the_value_on_write(): void
    {
        $product = new Product;
        $product->name = 'JOHN';

        $this->assertSame('john', $product->getRawAttributes()['_source']['name']);
    }

    #[Test]
    public function appended_accessors_appear_in_to_array(): void
    {
        $product = (new Product)->setRawAttributes(['_source' => ['sku' => 'XYZ']]);

        $array = $product->toArray();

        $this->assertSame('Product: XYZ', $array['display_name']);
    }
}
