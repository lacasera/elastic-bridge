<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Fixtures;

use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Lacasera\ElasticBridge\ElasticBridge;

class Product extends ElasticBridge
{
    protected $index = 'products';

    protected $casts = [
        'in_stock' => 'boolean',
        'quantity' => 'integer',
        'weight' => 'float',
        'price' => 'decimal:2',
        'sku' => 'string',
        'tags' => 'array',
        'meta' => 'object',
        'options' => 'collection',
        'published_at' => 'datetime',
        'archived_at' => 'immutable_datetime',
        'released_on' => 'date',
        'seen_at' => 'timestamp',
        'secret' => 'encrypted',
        'secret_list' => 'encrypted:array',
        'password' => 'hashed',
        'currency' => Currency::class,
        'statuses' => AsEnumCollection::class.':'.Currency::class,
        'address' => AsAddress::class,
    ];

    protected $appends = ['display_name'];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value): string => ucfirst((string) $value),
            set: fn ($value) => strtolower((string) $value),
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes): string => 'Product: '.($attributes['sku'] ?? ''),
        );
    }
}
