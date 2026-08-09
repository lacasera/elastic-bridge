<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Concerns;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

trait HasCasts
{
    /**
     * Cached class-cast caster instances, keyed by attribute.
     *
     * @var array<string, mixed>
     */
    protected array $classCastCache = [];

    /**
     * Primitive cast types matched case-insensitively by their full string.
     *
     * @var string[]
     */
    protected static array $primitiveCastTypes = [
        'int', 'integer', 'real', 'float', 'double', 'decimal', 'string', 'bool', 'boolean',
        'object', 'array', 'json', 'collection', 'date', 'datetime', 'immutable_date',
        'immutable_datetime', 'timestamp', 'hashed',
        'encrypted', 'encrypted:array', 'encrypted:collection', 'encrypted:object',
    ];

    /**
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        $casts = property_exists($this, 'casts') ? (array) $this->casts : [];

        return array_merge($casts, method_exists($this, 'casts') ? $this->casts() : []);
    }

    public function hasCast(string $key, array|string|null $types = null): bool
    {
        if (! array_key_exists($key, $this->getCasts())) {
            return false;
        }

        return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
    }

    /**
     * Cast a stored value into its rich representation for attribute access.
     */
    public function castAttribute(string $key, mixed $value): mixed
    {
        $castType = $this->getCastType($key);

        if (is_null($value) && in_array($castType, static::$primitiveCastTypes, true)) {
            return null;
        }

        switch ($castType) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return $this->fromFloat($value);
            case 'decimal':
                return $this->asDecimal($value, (int) $this->getCastArgument($key));
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return $this->fromJson($value, associative: false);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            case 'collection':
                return new Collection($this->fromJson($value));
            case 'date':
                return $this->asDate($value);
            case 'datetime':
                return $this->asDateTime($value, $this->getCastArgument($key));
            case 'immutable_date':
                return $this->asDate($value)->toImmutable();
            case 'immutable_datetime':
                return $this->asDateTime($value, $this->getCastArgument($key))->toImmutable();
            case 'timestamp':
                return $this->asTimestamp($value);
            case 'encrypted':
                return $this->fromEncrypted($value);
            case 'encrypted:array':
                return $this->fromEncrypted($value, 'array');
            case 'encrypted:collection':
                return new Collection($this->fromEncrypted($value, 'array'));
            case 'encrypted:object':
                return $this->fromEncrypted($value, 'object');
            case 'hashed':
                return $value;
        }

        if ($this->isEnumCastable($key)) {
            return is_null($value) ? null : $this->getEnumCast($key)::from($value);
        }

        if ($this->isDateTimeClassCast($key)) {
            return $this->asDateTime($value);
        }

        if ($this->isClassCastable($key)) {
            return $this->getClassCaster($key)->get($this, $key, $value, $this->rawSource());
        }

        return $value;
    }

    /**
     * Convert a rich value into its stored form and write it into `_source`.
     */
    public function castAndStore(string $key, mixed $value): void
    {
        if ($value !== null && $this->isEnumCastable($key)) {
            data_set($this->attributes, '_source.'.$key, $value instanceof BackedEnum ? $value->value : $value);

            return;
        }

        if ($this->isClassCastable($key)) {
            $set = $this->getClassCaster($key)->set($this, $key, $value, $this->rawSource());

            foreach (is_array($set) ? $set : [$key => $set] as $storeKey => $storeValue) {
                data_set($this->attributes, '_source.'.$storeKey, $storeValue);
            }

            return;
        }

        data_set($this->attributes, '_source.'.$key, $this->castForStorage($key, $value));
    }

    /**
     * Reduce a cast value to a JSON-serializable form for `toArray()`.
     */
    public function serializeCast(string $key, mixed $value): mixed
    {
        if ($this->isClassCastable($key)) {
            $caster = $this->getClassCaster($key);
            $cast = $caster->get($this, $key, $value, $this->rawSource());

            if ($caster instanceof SerializesCastableAttributes) {
                return $caster->serialize($this, $key, $cast, $this->rawSource());
            }

            return $cast instanceof Arrayable ? $cast->toArray() : $cast;
        }

        $cast = $this->castAttribute($key, $value);

        return match (true) {
            $cast instanceof DateTimeInterface => $this->serializeDate($cast),
            $cast instanceof BackedEnum => $cast->value,
            $cast instanceof Arrayable => $cast->toArray(),
            default => $cast,
        };
    }

    protected function castForStorage(string $key, mixed $value): mixed
    {
        $type = $this->getCastType($key);

        return match (true) {
            in_array($type, ['int', 'integer'], true) => (int) $value,
            in_array($type, ['real', 'float', 'double'], true) => (float) $value,
            $type === 'decimal' => $this->asDecimal($value, (int) $this->getCastArgument($key)),
            $type === 'string' => (string) $value,
            in_array($type, ['bool', 'boolean'], true) => (bool) $value,
            in_array($type, ['array', 'json', 'collection', 'object'], true) => $this->toNativeArray($value),
            in_array($type, ['date', 'datetime', 'immutable_date', 'immutable_datetime'], true) => $this->fromDateTime($value, $key),
            $type === 'timestamp' => $this->asTimestamp($value),
            $type === 'hashed' => $this->hashValue($value),
            $type === 'encrypted' => Crypt::encrypt($value, serialize: false),
            in_array($type, ['encrypted:array', 'encrypted:collection'], true) => Crypt::encrypt(json_encode($this->toNativeArray($value)), serialize: false),
            $type === 'encrypted:object' => Crypt::encrypt(json_encode($value), serialize: false),
            $this->isDateTimeClassCast($key) => $this->fromDateTime($value, $key),
            default => $value,
        };
    }

    protected function getCastType(string $key): string
    {
        $cast = (string) $this->getCasts()[$key];
        $lower = strtolower($cast);

        return match (true) {
            str_starts_with($lower, 'decimal:') => 'decimal',
            str_starts_with($lower, 'datetime:') => 'datetime',
            str_starts_with($lower, 'date:') => 'date',
            str_starts_with($lower, 'immutable_datetime:') => 'immutable_datetime',
            str_starts_with($lower, 'immutable_date:') => 'immutable_date',
            in_array($lower, static::$primitiveCastTypes, true) => $lower,
            default => $cast,
        };
    }

    protected function getCastArgument(string $key): ?string
    {
        $cast = (string) $this->getCasts()[$key];

        return str_contains($cast, ':') ? explode(':', $cast, 2)[1] : null;
    }

    protected function isEnumCastable(string $key): bool
    {
        $casts = $this->getCasts();

        if (! array_key_exists($key, $casts)) {
            return false;
        }

        $cast = $casts[$key];

        return is_string($cast) && ! in_array(strtolower($cast), static::$primitiveCastTypes, true) && enum_exists($cast);
    }

    protected function getEnumCast(string $key): string
    {
        return $this->getCasts()[$key];
    }

    protected function isDateTimeClassCast(string $key): bool
    {
        $cast = $this->getCasts()[$key] ?? null;

        return is_string($cast) && class_exists($cast) && is_a($cast, DateTimeInterface::class, true);
    }

    protected function isClassCastable(string $key): bool
    {
        $casts = $this->getCasts();

        if (! array_key_exists($key, $casts)) {
            return false;
        }

        $castType = $this->parseCasterClass($casts[$key]);

        if (in_array(strtolower((string) $castType), static::$primitiveCastTypes, true)) {
            return false;
        }

        if ($this->isEnumCastable($key) || $this->isDateTimeClassCast($key)) {
            return false;
        }

        if (class_exists($castType)) {
            return true;
        }

        throw new InvalidArgumentException(sprintf('Invalid cast [%s] on attribute [%s].', $castType, $key));
    }

    protected function getClassCaster(string $key): CastsAttributes|CastsInboundAttributes
    {
        if (isset($this->classCastCache[$key])) {
            return $this->classCastCache[$key];
        }

        [$class, $arguments] = $this->parseCasterClassWithArguments($this->getCasts()[$key]);

        $caster = is_a($class, Castable::class, true)
            ? $class::castUsing($arguments)
            : new $class(...$arguments);

        return $this->classCastCache[$key] = $caster;
    }

    protected function parseCasterClass(string $cast): string
    {
        return str_contains($cast, ':') ? explode(':', $cast, 2)[0] : $cast;
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    protected function parseCasterClassWithArguments(string $cast): array
    {
        if (! str_contains($cast, ':')) {
            return [$cast, []];
        }

        [$class, $arguments] = explode(':', $cast, 2);

        return [$class, explode(',', $arguments)];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rawSource(): array
    {
        return $this->attributes['_source'] ?? [];
    }

    protected function toNativeArray(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if (is_object($value)) {
            return json_decode(json_encode($value), true);
        }

        return $value;
    }

    protected function fromJson(mixed $value, bool $associative = true): mixed
    {
        if (is_array($value)) {
            return $associative ? $value : json_decode(json_encode($value), false);
        }

        if (is_string($value)) {
            return json_decode($value, $associative);
        }

        return $value;
    }

    protected function fromEncrypted(mixed $value, ?string $decode = null): mixed
    {
        $decrypted = Crypt::decrypt($value, unserialize: false);

        return match ($decode) {
            'array' => json_decode((string) $decrypted, true),
            'object' => json_decode((string) $decrypted, false),
            default => $decrypted,
        };
    }

    protected function fromFloat(mixed $value): float
    {
        return match ((string) $value) {
            'Infinity' => INF,
            '-Infinity' => -INF,
            'NaN' => NAN,
            default => (float) $value,
        };
    }

    protected function asDecimal(mixed $value, int $decimals): string
    {
        return number_format((float) $value, $decimals, '.', '');
    }

    protected function asDate(mixed $value): Carbon
    {
        return $this->asDateTime($value)->startOfDay();
    }

    protected function asDateTime(mixed $value, ?string $format = null): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance(Carbon::parse($value->format('Y-m-d H:i:s.u'), $value->getTimezone()));
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        if ($format) {
            return Carbon::createFromFormat($format, (string) $value) ?: Carbon::parse($value);
        }

        return Carbon::parse($value);
    }

    protected function asTimestamp(mixed $value): int
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    protected function fromDateTime(mixed $value, ?string $key = null): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $format = $key ? $this->getCastArgument($key) : null;

        return $this->asDateTime($value, $format)->format($format ?? $this->getDateFormat());
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format($this->getDateFormat());
    }

    protected function getDateFormat(): string
    {
        return property_exists($this, 'dateFormat') && $this->dateFormat ? $this->dateFormat : DATE_ATOM;
    }

    protected function hashValue(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return Hash::isHashed($value) ? $value : Hash::make($value);
    }
}
