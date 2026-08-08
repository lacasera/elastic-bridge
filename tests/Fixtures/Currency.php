<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Fixtures;

enum Currency: string
{
    case USD = 'usd';
    case EUR = 'eur';
    case GBP = 'gbp';
}
