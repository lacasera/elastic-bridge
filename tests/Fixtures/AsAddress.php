<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Override;

/**
 * A custom cast written for a bridge — note the untyped $model parameter, since
 * a bridge is not an Eloquent Model.
 *
 * @implements CastsAttributes<Address, Address>
 */
class AsAddress implements CastsAttributes
{
    #[Override]
    public function get($model, string $key, $value, array $attributes): ?Address
    {
        if (! isset($attributes['address_line_one'])) {
            return null;
        }

        return new Address($attributes['address_line_one'], $attributes['address_line_two'] ?? '');
    }

    #[Override]
    public function set($model, string $key, $value, array $attributes): array
    {
        return [
            'address_line_one' => $value->lineOne,
            'address_line_two' => $value->lineTwo,
        ];
    }
}
