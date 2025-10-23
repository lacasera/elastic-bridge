<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @see \Lacasera\ElasticBridge\ElasticBridge
 */
class ElasticBridge extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \Lacasera\ElasticBridge\ElasticBridge::class;
    }
}
