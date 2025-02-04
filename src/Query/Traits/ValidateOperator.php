<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Query\Traits;

trait ValidateOperator
{
    public static function isValid(string $operator): bool
    {
        return in_array(self::tryFrom($operator), self::cases());
    }
}
