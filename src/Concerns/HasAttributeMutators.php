<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;

trait HasAttributeMutators
{
    /**
     * Whether a camelCase method defines an `Attribute` accessor for the key.
     */
    public function hasAttributeGetMutator(string $key): bool
    {
        return $this->resolveAttributeMutator($key)?->get !== null;
    }

    /**
     * Whether the key's `Attribute` defines a set (mutator) transformation.
     */
    public function hasAttributeSetMutator(string $key): bool
    {
        return $this->resolveAttributeMutator($key)?->set !== null;
    }

    /**
     * Run the accessor `get` transformation.
     */
    public function mutateAttribute(string $key, mixed $value): mixed
    {
        $get = $this->resolveAttributeMutator($key)->get;

        return $get($value, $this->rawSource());
    }

    /**
     * Run the mutator `set` transformation and write the result into `_source`.
     * A returned array writes multiple keys (e.g. a value object spread over columns).
     */
    public function setMutatedAttributeValue(string $key, mixed $value): void
    {
        $set = $this->resolveAttributeMutator($key)->set;

        $result = $set($value, $this->rawSource());

        foreach (is_array($result) ? $result : [$key => $result] as $storeKey => $storeValue) {
            data_set($this->attributes, '_source.'.$storeKey, $storeValue);
        }
    }

    /**
     * Attributes to append to the array/JSON form (accessor-only values).
     *
     * @return array<int, string>
     */
    public function getAppends(): array
    {
        return property_exists($this, 'appends') ? (array) $this->appends : [];
    }

    /**
     * Resolve the `Attribute` instance for a key, or null when none is defined.
     */
    protected function resolveAttributeMutator(string $key): ?Attribute
    {
        // Nested keys (dot notation) map to a camelCased method of the underscored
        // path, e.g. "hotel.location.lat" -> hotelLocationLat().
        $method = Str::camel(str_replace('.', '_', $key));

        if (! method_exists($this, $method)) {
            return null;
        }

        $returnType = (new ReflectionMethod($this, $method))->getReturnType();

        if (! $returnType instanceof ReflectionNamedType || $returnType->getName() !== Attribute::class) {
            return null;
        }

        return $this->{$method}();
    }
}
