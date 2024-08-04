<?php

namespace Lacasera\ElasticBridge;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Lacasera\ElasticBridge\Builder\BridgeBuilder;

class ElasticBridge
{
    use ForwardsCalls;

    public function newBridgeQuery(): BridgeBuilder
    {
        return new BridgeBuilder;
    }

    public function getIndex(): string
    {
        return $this->index ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    /**
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->newBridgeQuery(), $method, $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = new self;

        return $instance->forwardCallTo($instance->newBridgeQuery(), $method, $parameters);
    }
}
