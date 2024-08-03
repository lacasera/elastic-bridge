<?php

namespace Lacasera\ElasticBridge\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Lacasera\ElasticBridge\ElasticBridge
 */
class ElasticBridge extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Lacasera\ElasticBridge\ElasticBridge::class;
    }
}
