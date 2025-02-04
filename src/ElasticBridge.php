<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonException;
use Lacasera\ElasticBridge\Builder\BridgeBuilder;
use Lacasera\ElasticBridge\Concerns\FakeBridge;
use Lacasera\ElasticBridge\Concerns\HasAttributes;
use Lacasera\ElasticBridge\Concerns\HasCollection;
use Lacasera\ElasticBridge\DTO\Bucket;
use Lacasera\ElasticBridge\DTO\Stats;
use Lacasera\ElasticBridge\Exceptions\ErrorEncodingJson;

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

    /**
     * @return $this
     */
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

        $meta = $items['hits']['total'];

        if (isset($items['aggregations'])) {
            $this->setAggregateMarco($items['aggregations']);
        }

        return $instance->newCollection(array_map(function ($item) use ($instance, $meta) {
            return $instance->newFromBuilder($item, $meta);
        }, $items['hits']['hits']));
    }

    /**
     * @return $this
     */
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
        return static::query()->all($columns);
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

    public function __set(string $name, mixed $value): void
    {
        data_set($this->attributes, "_source.$name", $value);
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
     * @throws ErrorEncodingJson
     */
    public function toJson($options = 0)
    {
        try {
            $json = json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ErrorEncodingJson::forBridge($this, $e->getMessage());
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

    protected function setAggregateMarco(array $aggregations): void
    {
        $key = Arr::first(array_keys($aggregations));
        $name = Str::camel($key);

        $results = $this->resolveAggregationResults($aggregations, $key);

        Collection::macro($name, fn () => $results);
    }

    /**
     * @return array|\Illuminate\Support\Collection|Stats|mixed|void
     */
    protected function resolveAggregationResults(array $aggregations, $key)
    {
        if (str_contains($key, 'stats')) {
            return new Stats(data_get($aggregations, $key));
        }

        if ($this->isBucketAggregate($key)) {
            return collect(data_get($aggregations, "$key.buckets"))->mapInto(Bucket::class)->collect();
        }

        if (Arr::has($aggregations, $key)) {
            return data_get($aggregations, $key);
        }
    }

    protected function isBucketAggregate($key): bool
    {
        $key = Arr::first(explode('_', $key));

        return in_array($key, ['histogram', 'range']);
    }
}
