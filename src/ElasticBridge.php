<?php

namespace Lacasera\ElasticBridge;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Lacasera\ElasticBridge\Builder\BridgeBuilder;

abstract class ElasticBridge
{
    use ForwardsCalls;

    /**
     * The index associated with the bridge
     * @var string
     */
    protected $index;

    public function newBridgeQuery(): BridgeBuilder
    {
        return (new BridgeBuilder)->setBridge($this);
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

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static())->$method(...$parameters);
    }
}
