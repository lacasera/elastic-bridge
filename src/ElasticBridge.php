<?php

namespace Lacasera\ElasticBridge;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonException;
use Lacasera\ElasticBridge\Builder\BridgeBuilder;
use Lacasera\ElasticBridge\Concerns\HasAttributes;
use Lacasera\ElasticBridge\Concerns\HasCollection;
use Lacasera\ElasticBridge\Exceptions\JsonEncodingException;

abstract class ElasticBridge
{
    use ForwardsCalls;
    use HasAttributes;
    use HasCollection;

    /**
     * The index associated with the bridge
     *
     * @var string
     */
    protected $index;

    public $exists = false;

    protected static string $collectionClass = Collection::class;

    /**
     * @return BridgeBuilder
     */
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
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * @param $attributes
     * @param $exists
     * @return $this
     */
    public function newInstance($attributes = [], $exists = true)
    {
        $bridge = new static;

        $bridge->exists = $exists;

        $bridge->setIndex($this->getIndex());

        return $bridge;
    }

    public function hydrate(array $items)
    {
        $instance = $this->newInstance();

        return $instance->newCollection(array_map(function ($item) use ($items, $instance) {
            return $instance->newFromBuilder($item);
        }, $items));
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = $this->newInstance([], true);

        $model->setRawAttributes($attributes, true);

        return $model;
    }

    public static function all($columns = ['*'])
    {
        return static::query()->get(
            is_array($columns) ? $columns : func_get_args()
        );
    }

    public static function query()
    {
        return (new static)->newBridgeQuery();
    }

    /**
     * @param string $key
     * @return array|mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * @param string $index
     * @return $this
     */
    public function setIndex(string $index)
    {
        $this->index = $index;

        return $this;
    }

    public function toJson($options = 0)
    {
        try {
            $json = json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw JsonEncodingException::forBridge($this, $e->getMessage());
        }

        return $json;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->attributesToArray();
    }

    /**
     * @return array
     */
    public function attributesToArray()
    {
        return $this->attributes;
    }
}
