<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonException;
use JsonSerializable;
use Lacasera\ElasticBridge\Builder\BridgeBuilder;
use Lacasera\ElasticBridge\Concerns\FakeBridge;
use Lacasera\ElasticBridge\Concerns\HasAttributeMutators;
use Lacasera\ElasticBridge\Concerns\HasAttributes;
use Lacasera\ElasticBridge\Concerns\HasCasts;
use Lacasera\ElasticBridge\Concerns\HasCollection;
use Lacasera\ElasticBridge\DTO\Bucket;
use Lacasera\ElasticBridge\DTO\Stats;
use Lacasera\ElasticBridge\Exceptions\ErrorEncodingJson;
use Override;

/**
 * @property-read string|int|null $id
 */
abstract class ElasticBridge implements Arrayable, Jsonable, JsonSerializable
{
    use FakeBridge;
    use ForwardsCalls;
    use HasAttributeMutators;
    use HasAttributes;
    use HasCasts;
    use HasCollection;

    /**
     * The index associated with the bridge
     *
     * @var string
     */
    protected $index;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * The accessors to append to the array/JSON form.
     *
     * @var array<int, string>
     */
    protected $appends = [];

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
        $static = (new static);

        return $static->forwardCallTo($static->newBridgeQuery(), $method, $parameters);
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

        $collection = $elasticBridge->newCollection(array_map(fn ($item): ElasticBridge => $elasticBridge->newFromBuilder($item, $meta), $items['hits']['hits']));

        if (isset($items['aggregations'])) {
            $collection->setAggregations($this->resolveAggregations($items['aggregations']));
        }

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
     * Get all records with cursor pagination using match_all query.
     *
     * @param  int  $perPage  Number of records per page (default: 15)
     * @return mixed
     */
    public static function all(int $perPage = 15)
    {
        return static::query()->all($perPage);
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
        $this->setAttribute($name, $value);
    }

    /**
     * Set an attribute, applying any mutator or cast before storage.
     */
    public function setAttribute(string $key, mixed $value): static
    {
        if ($this->hasAttributeSetMutator($key)) {
            $this->setMutatedAttributeValue($key, $value);

            return $this;
        }

        if ($this->hasCast($key)) {
            $this->castAndStore($key, $value);

            return $this;
        }

        data_set($this->attributes, '_source.'.$key, $value);

        return $this;
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
    #[Override]
    public function toJson($options = 0)
    {
        try {
            $json = json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw ErrorEncodingJson::forBridge($this, $jsonException->getMessage());
        }

        return $json;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    #[Override]
    public function toArray(): array
    {
        return $this->attributesToArray();
    }

    /**
     * Get the instance as an array.
     * Returns the _source data (with casts, mutators, and appends applied).
     *
     * @return array
     */
    public function attributesToArray()
    {
        $source = $this->attributes['_source'] ?? null;

        if (! is_array($source)) {
            return $this->attributes;
        }

        $casts = $this->getCasts();

        $result = [];

        foreach ($source as $key => $value) {
            if ($this->hasAttributeGetMutator($key)) {
                $result[$key] = $this->serializeAttributeValue($this->mutateAttribute($key, $value));
            } elseif (array_key_exists($key, $casts)) {
                $result[$key] = $this->serializeCast($key, $value);
            } else {
                $result[$key] = $value;
            }
        }

        foreach ($this->getAppends() as $key) {
            $result[$key] = $this->serializeAttributeValue($this->mutateAttribute($key, null));
        }

        return $result;
    }

    /**
     * Reduce a mutated/accessor value to a JSON-serializable form.
     */
    protected function serializeAttributeValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof DateTimeInterface => $this->serializeDate($value),
            $value instanceof BackedEnum => $value->value,
            $value instanceof Arrayable => $value->toArray(),
            default => $value,
        };
    }

    /**
     * Get all raw attributes including Elasticsearch metadata (_id, _index, _score, etc.)
     */
    public function getRawAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Resolve every aggregation in the response into a name => result map,
     * keyed by the camel-cased aggregation name for instance-scoped lookup.
     *
     * @return array<string, mixed>
     */
    protected function resolveAggregations(array $aggregations): array
    {
        $resolved = [];

        foreach (array_keys($aggregations) as $key) {
            $resolved[Str::camel((string) $key)] = $this->resolveAggregationResults($aggregations, (string) $key);
        }

        return $resolved;
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
