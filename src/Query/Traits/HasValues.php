<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Traits;

trait HasValues
{
    public static function values()
    {
        return array_map(fn($enum) => $enum->value, self::cases());
    }
}
