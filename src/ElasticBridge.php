<?php

namespace Lacasera\ElasticBridge;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonException;
use Lacasera\ElasticBridge\Builder\BridgeBuilder;
use Lacasera\ElasticBridge\Concerns\FakeBridge;
use Lacasera\ElasticBridge\Concerns\HasAttributes;
use Lacasera\ElasticBridge\Concerns\HasCollection;
use Lacasera\ElasticBridge\Exceptions\JsonEncodingException;

abstract class ElasticBridge
{
    use FakeBridge;
    use ForwardsCalls;
    use HasAttributes;
    use HasCollection;

    /**
     * The index associated with the bridge
     *
     * @var string
     */
    protected $index;

    /**
     * @var bool
     */
    public $exists = false;

    protected static string $collectionClass = Collection::class;

    public function newBridgeQuery(): BridgeBuilder
    {
        return (new BridgeBuilder)->setBridge($this);
    }

    public function getIndex(): string
    {
        return $this->index ?: Str::snake(Str::pluralStudly(class_basename($this)));
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

    public function newInstance(array $attributes = [], bool $exists = true): ElasticBridge
    {
        $bridge = new static;

        $bridge->exists = $exists;

        $bridge->setIndex($this->getIndex());

        return $bridge;
    }

    public function hydrate(array $items, bool $isPaginating = false): mixed
    {
        $instance = $this->newInstance();

        if ($isPaginating) {
            static::$collectionClass = PaginatedCollection::class;
        }

        $meta = $items['total'];

        return $instance->newCollection(array_map(function ($item) use ($instance, $meta) {
            return $instance->newFromBuilder($item, $meta);
        }, $items['hits']));
    }

    public function newFromBuilder(array $attributes = [], array $meta = [], $connection = null): ElasticBridge
    {
        $bridge = $this->newInstance([], true);

        $bridge->setRawAttributes($attributes, $meta, true);

        return $bridge;
    }

    /**
     * @return mixed
     */
    public static function all($columns = ['*'])
    {
        return static::query()->get(
            is_array($columns) ? $columns : func_get_args()
        );
    }

    /**
     * @return BridgeBuilder
     */
    public static function query()
    {
        return (new static)->newBridgeQuery();
    }

    /**
     * @return array|mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * @return $this
     */
    public function setIndex(string $index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return false|string
     *
     * @throws JsonEncodingException
     */
    public function toJson($options = 0)
    {
        try {
            $json = json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw JsonEncodingException::forBridge($this, $e->getMessage());
        }

        return $json;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
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
