## ElasticBridge

ElasticBridge gives you an Eloquent-style query builder for **Elasticsearch and OpenSearch**.
You define a "bridge" class per index and query it like a model. Full docs:
https://elasticbridge.dev (LLM-friendly: https://elasticbridge.dev/llms.txt).

### Conventions

- Bridges extend `Lacasera\ElasticBridge\ElasticBridge` and live in `App\Bridges`.
- Generate one with `php artisan make:bridge HotelRoom`.
- The index name defaults to the snake-cased plural of the class; override with
  `protected $index`. `$index` may be a wildcard/comma pattern for reads
  (`'logs-*'`); set `protected $writeIndex` (or override `getWriteIndex()`) for a concrete
  write target.
- You must set a term-level context (e.g. `asBoolean()`, `asRaw()`) before building a query,
  or a `MissingTermLevelQuery` exception is thrown.
- Configuration lives in `config/elasticbridge.php`; env vars use the `SEARCH_*` prefix and a
  `SEARCH_DRIVER` of `elasticsearch` (default) or `opensearch`.

### Querying

@verbatim
<code-snippet name="Boolean search with filters, sorting, pagination" lang="php">
use App\Bridges\HotelRoom;

$rooms = HotelRoom::asBoolean()
    ->mustMatch('advertiser', 'booking.com')
    ->filterByTerm('code', 'usd')
    ->filterByRange('price', 20, 'gte')   // operators: gt, gte, lt, lte
    ->orderBy('price', 'DESC')
    ->cursorPaginate(50)
    ->get(['price', 'advertiser']);       // select fields
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Find by id and inspect the built query" lang="php">
$room = HotelRoom::find(1);                 // single id → instance; array → collection
$body = HotelRoom::asRaw()->matchAll()->toQuery(); // inspect the DSL without executing
</code-snippet>
@endverbatim

### Writing

@verbatim
<code-snippet name="Create, save, and bulk write" lang="php">
$id = HotelRoom::create(['price' => 100, 'code' => 'usd']); // returns _id

$room = HotelRoom::find(1);
$room->price = 120;
$room->save();

// bulk index / upsert return a BulkResult (count(), failed(), hasErrors())
HotelRoom::bulk([['id' => 1, 'price' => 100], ['price' => 200]]);
HotelRoom::upsert([['id' => 1, 'price' => 150]]); // upsert requires an id per row
</code-snippet>
@endverbatim

### Casting

@verbatim
<code-snippet name="Attribute casting on a bridge" lang="php">
class HotelRoom extends \Lacasera\ElasticBridge\ElasticBridge
{
    protected $casts = [
        'in_stock'     => 'boolean',
        'price'        => 'decimal:2',
        'published_at' => 'datetime',
        'currency'     => \App\Enums\Currency::class, // backed enum
    ];
}
</code-snippet>
@endverbatim

### Aggregations

@verbatim
<code-snippet name="Aggregates" lang="php">
HotelRoom::avg('price');   // also: min, max, sum, count
$stats = HotelRoom::stats('price');

$rooms = HotelRoom::asBoolean()->matchAll()->withAggregate('avg', 'price')->get();
echo $rooms->priceAvg(); // read the aggregate off the returned collection
</code-snippet>
@endverbatim

### Testing

@verbatim
<code-snippet name="Fake the connection in tests" lang="php">
HotelRoom::fake([
    'hits' => ['total' => ['value' => 0, 'relation' => 'eq'], 'hits' => []],
]);

$query = HotelRoom::asBoolean()->matchAll()->toQuery();
</code-snippet>
@endverbatim

For anything deeper (multi-index `from()`/`into()`, drivers/auth, geo filters, accessors &
mutators, custom casts), use the `elastic-bridge-development` skill or consult
https://elasticbridge.dev/llms-full.txt.
