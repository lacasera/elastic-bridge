---
name: elastic-bridge-development
description: Build and work with lacasera/elastic-bridge — an Eloquent-style query builder for Elasticsearch and OpenSearch in Laravel. Use when creating "bridge" classes, writing search/aggregation queries, casting attributes, bulk indexing/upserting, targeting multiple indexes, configuring the Elasticsearch/OpenSearch driver, or testing search code.
---

# ElasticBridge Development

ElasticBridge lets you query Elasticsearch and OpenSearch with an Eloquent-like API. You define
a bridge class per index and query it fluently. Docs: https://elasticbridge.dev
(LLM: https://elasticbridge.dev/llms-full.txt).

## When to use this skill

Use it when working with `lacasera/elastic-bridge`: generating bridges, building search or
aggregation queries, casting attributes, bulk writes, multi-index reads/writes, driver/auth
configuration, or testing.

## Generating a bridge

```bash
php artisan make:bridge HotelRoom
```

```php
namespace App\Bridges;

use Lacasera\ElasticBridge\ElasticBridge;

class HotelRoom extends ElasticBridge
{
    // Defaults to the snake_case plural of the class ("hotel_rooms").
    protected $index = 'hotel-rooms';
}
```

## Term-level context (required)

Set a query context before adding clauses, or a `MissingTermLevelQuery` exception is thrown:
`asBoolean()`, `asRaw()`, `asTerm()`, `asTerms()`, `asRange()`, `asIds()`, `asFuzzy()`,
`asPrefix()`, `asWildCard()`, `asMatch()`, `asTermSet()`.

## Querying

```php
use App\Bridges\HotelRoom;

$rooms = HotelRoom::asBoolean()
    ->mustMatch('advertiser', 'booking.com') // must clause
    ->shouldMatch('city', 'accra')           // should clause
    ->mustExist('price')
    ->orderBy('price', 'DESC')               // asc|desc only; else InvalidQuery
    ->cursorPaginate(50)
    ->get(['price', 'advertiser']);          // select fields
```

- Full-text: `match($field, $query, $options)` and `orMatch()` (use with `asRaw()`/`asMatch()`);
  `multiMatch($fields, $query)` and `matchPhrase($field, $query, $options)` nest themselves as
  `bool.must` automatically (no `asRaw()` needed). Placing `match()` under `asBoolean()` throws.
- Chaining multiple clauses of the same type is valid (they render as arrays).
- Inspect without executing: `HotelRoom::asRaw()->matchAll()->toQuery()` (add `asJson: true`).

### Filters

```php
HotelRoom::asBoolean()
    ->filterByTerm('code', 'usd')
    ->filterByRange('price', 100, 'gte') // operators: gt, gte, lt, lte (from/to removed)
    ->range('price', 'lte', 500)         // chainable range operators
    ->get();
```

Geo filters: `filterByGeoBoundingBox`, `filterByGeoDistance`, `filterByGeoPolygon`,
`filterByGeoDistanceRange`, `filterByGeoShape`. Filters used without `asBoolean()` are promoted
into a `bool` automatically.

### Pagination

- `simplePaginate(size, from)` — offset based.
- `cursorPaginate(size, sort)` — `search_after`; call `$results->links()` for
  `['previous' => [...], 'next' => [...], 'total' => N]`.

## Aggregations

```php
HotelRoom::avg('price'); // min, max, sum, count, avg
$stats = HotelRoom::stats('price'); // Stats DTO: count(), avg(), min(), max(), sum()

$rooms = HotelRoom::asBoolean()->matchAll()
    ->withAggregate('avg', 'price')
    ->withAggregate('histogram', 'price', ['interval' => 100])
    ->get();

echo $rooms->priceAvg();        // <field><Aggregate>() on the returned collection
$rooms->priceHistogram();       // collection of Bucket DTOs
```

## Attribute casting & accessors

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends \Lacasera\ElasticBridge\ElasticBridge
{
    protected $casts = [
        'in_stock'     => 'boolean',
        'price'        => 'decimal:2',
        'published_at' => 'datetime',       // date/immutable_datetime/timestamp too
        'currency'     => \App\Enums\Currency::class, // backed enum
        'options'      => 'collection',
    ];

    protected $appends = ['display_name'];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst((string) $value),
            set: fn ($value) => strtolower((string) $value),
        );
    }
}
```

Full Eloquent cast catalog is supported (primitives, dates, `decimal:n`, enums, enum
collections, `encrypted*`, `hashed`, `As*` casts, custom `CastsAttributes`). Custom casts must
use an **untyped `$model`** parameter — a bridge is not an Eloquent model.

### Nested attributes (dot notation)

Documents are nested JSON. Cast, accessor, and mutator keys may use dot notation, and nested
values are assigned/retrieved by their dotted key.

```php
protected $casts = [
    'hotel.location.lat' => 'float',
    'hotel.opened_at'    => 'datetime',
];

// nested accessor/mutator: method = camelCase of the underscored path
protected function hotelLocationLat(): Attribute { /* key: hotel.location.lat */ }

$product->setAttribute('hotel.location.lat', '5.6');   // cast + stored nested
$product->getAttribute('hotel.location.lat');          // 5.6 (float)
```

`toArray()`/`toJson()` serialize nested casts and appended nested accessors in place. Reading a
parent as an object (`$product->hotel->location->lat`) returns the raw value — use the dotted key
for the cast value. Filters/queries accept dotted field paths directly.

## Writing documents

```php
$id = HotelRoom::create(['price' => 100, 'code' => 'usd']); // returns _id

$room = HotelRoom::find(1);
$room->price = 120;
$room->save();                 // update; creates if missing
$room->increment('price', 5);  // and decrement()

// Bulk: returns a BulkResult (count(), total(), failed(), hasErrors())
HotelRoom::bulk([['id' => 1, 'price' => 100], ['price' => 200]]);
HotelRoom::upsert([['id' => 1, 'price' => 150]]); // each row needs an id
```

Bulk payloads are chunked and capped via `config('elasticbridge.bulk')`.

## Multiple indexes

```php
class Log extends \Lacasera\ElasticBridge\ElasticBridge
{
    protected $index = 'logs-*'; // read across a wildcard/comma pattern

    public function getWriteIndex(): string
    {
        return 'logs-'.date('Y.m.d'); // concrete write target (or set $writeIndex)
    }
}

Log::from('logs-2025.02.01,logs-2025.02.02')->asBoolean()->matchAll()->get(); // per-call read
Log::into('logs-2025.02.02')->create(['message' => 'hi']);                    // per-call write
```

Writing to a wildcard throws `AmbiguousWriteIndex`. A document read from a wildcard writes back
to the concrete `_index` it came from.

## Driver & configuration

`config/elasticbridge.php` (env `SEARCH_*`):

- `driver`: `elasticsearch` (default) or `opensearch` (`SEARCH_DRIVER`).
- `host`: comma-separated for a cluster (`SEARCH_HOST`). Elasticsearch load-balances across all
  hosts; OpenSearch uses the first (front a cluster with an endpoint/LB).
- `auth_method`: `basic-auth`, `api-key`, or `sigv4` (OpenSearch AWS; needs `aws/aws-sdk-php`
  and `SEARCH_AWS_REGION`).

## Testing

```php
$fake = HotelRoom::fake([
    'hits' => ['total' => ['value' => 0, 'relation' => 'eq'], 'hits' => []],
]);

$query = HotelRoom::asBoolean()->matchAll()->toQuery();
// $fake->requests[...] records the exact params sent per operation.
```

`::fake()` binds a driver-agnostic fake connection and returns it; no live cluster required.
