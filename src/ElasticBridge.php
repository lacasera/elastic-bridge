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

/**
 * @property-read string|int|null $id
 */
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
        $static = (new static); // ->$method(...$parameters);

        return $static->forwardCallTo($static->newBridgeQuery(), $method, $parameters); // $this->forwardCallTo($this->newBridgeQuery(), $method, $parameters);
    }

    public function newInstance(array $attributes = [], bool $exists = true): ElasticBridge
    {
        $static = new static;

        $static->exists = $exists;

        $static->setIndex($this->getIndex());

        return $static;
    }

    public function hydrate(array $items, bool $isPaginating = false): mixed
    {
        $elasticBridge = $this->newInstance();

        $originalCollectionClass = static::$collectionClass;

        if ($isPaginating) {
            static::$collectionClass = PaginatedCollection::class;
        }

        $meta = $items['hits']['total'];

        if (isset($items['aggregations'])) {
            $this->setAggregateMarco($items['aggregations']);
        }

        $collection = $elasticBridge->newCollection(array_map(fn ($item): \Lacasera\ElasticBridge\ElasticBridge => $elasticBridge->newFromBuilder($item, $meta), $items['hits']['hits']));

        static::$collectionClass = $originalCollectionClass;

        return $collection;
    }

    public function newFromBuilder(array $attributes = [], array $meta = [], $connection = null): ElasticBridge
    {
        $elasticBridge = $this->newInstance([], true);

        $elasticBridge->setRawAttributes($attributes, $meta, true);

        return $elasticBridge;
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
        data_set($this->attributes, '_source.'.$name, $value);
    }

    public function setIndex(string $index): ElasticBridge
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
        } catch (JsonException $jsonException) {
            throw ErrorEncodingJson::forBridge($this, $jsonException->getMessage());
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
        $key = (string) Arr::first(array_keys($aggregations));

        $name = Str::camel($key);

        $results = $this->resolveAggregationResults($aggregations, $key);

        Collection::macro($name, fn () => $results);
    }

    /**
     * @return array|\Illuminate\Support\Collection|Stats|mixed|void
     */
    protected function resolveAggregationResults(array $aggregations, string $key)
    {
        if (str_contains($key, 'stats')) {
            return new Stats(data_get($aggregations, $key));
        }

        if ($this->isBucketAggregate($key)) {
            return collect(data_get($aggregations, $key.'.buckets'))->mapInto(Bucket::class)->collect();
        }

        if (Arr::has($aggregations, $key)) {
            return data_get($aggregations, $key);
        }

        return null;
    }

    protected function isBucketAggregate($key): bool
    {
        $key = Arr::first(explode('_', (string) $key));

        return in_array($key, ['histogram', 'range']);
    }
}
