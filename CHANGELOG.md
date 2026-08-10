# Changelog

All notable changes to `elastic-bridge` will be documented in this file.

## [Unreleased]

## [2.0.0] - 2026-08-10

ElasticBridge 2.0 adds **OpenSearch** support alongside Elasticsearch, an attribute
**casting + accessors/mutators** layer, **bulk** write operations, **multi-index** queries,
and a hardened query builder. This is a major release with breaking changes — see the
[v1 → v2 upgrade guide](https://elasticbridge.dev/docs/v2/upgrade-guide).

### Added
- **OpenSearch support.** Choose the backend with `SEARCH_DRIVER` (`elasticsearch` | `opensearch`) — the same fluent API works across both. Authentication: basic-auth, API key, and AWS SigV4 (via the optional `aws/aws-sdk-php`).
- **Multi-host clusters.** `SEARCH_HOST` accepts a comma-separated list — Elasticsearch load-balances across all hosts; OpenSearch uses the given endpoint.
- **Attribute casting** via `$casts` (or a `casts()` method) with full Eloquent parity — primitives, dates (`date`/`datetime`/immutable/`timestamp`), `decimal:n`, backed enums, enum collections, `encrypted*`, `hashed`, the `As*` casts, and custom `CastsAttributes`; plus **accessors & mutators** via `Attribute::make` and `$appends`.
- **Nested (dot-notation) attributes.** Casts, accessors, and mutators apply to nested paths (e.g. `'hotel.location.lat' => 'float'`); values are assigned/retrieved by their dotted key and serialized in place in `toArray()`/`toJson()` (siblings preserved).
- **Bulk insert & upsert** — `Model::bulk([...])` and `Model::upsert([...])` with automatic chunking, a configurable record cap (`bulk.max` / `bulk.chunk_size`), and a `BulkResult` DTO (`successful()`, `failed()`, `count()`, `total()`, `hasErrors()`).
- **Multi-index queries.** Read from a wildcard/comma `$index` (e.g. `'logs-*'`) while writing to a concrete index (`$writeIndex` or an overridden `getWriteIndex()`); existing documents write back to their origin `_index`, and `from()` / `into()` override the read/write index per call.
- **Query validation.** `bool`, `match`, and `terms_set` queries are validated when built; `orderBy` and range operators are validated against `OrderOperator` / `RangeOperator`, throwing `InvalidQuery` on invalid input.
- **Laravel 12 & PHP 8.3** support (Carbon 3.8.4+, Orchestra Testbench 10.x).
- **AI-assisted development.** Ships a Laravel Boost skill and guidelines (`resources/boost/…`); the documentation is published in [`llms.txt`](https://elasticbridge.dev/llms.txt) format.
- `Lacasera\ElasticBridge\Testing\FakeConnection` — a driver-agnostic fake backing `::fake()`.

### Changed
- **BREAKING — environment variables renamed** `ELASTICSEARCH_*` → `SEARCH_*` (`SEARCH_DRIVER`, `SEARCH_HOST`, `SEARCH_USERNAME`, `SEARCH_PASSWORD`, `SEARCH_API_KEY`, `SEARCH_VERIFY_SSL`, `SEARCH_SSL_CERT`, plus `SEARCH_AWS_REGION`/`SEARCH_AWS_SERVICE`). There is no fallback — update your `.env`.
- **BREAKING — `ConnectionInterface`** now exposes `search()`, `count()`, `index()`, `update()`, and `bulk()` instead of a driver-specific `getClient()`. Custom connections must implement these methods.
- **BREAKING — `BridgeBuilder::all()`** signature is now `all(int $perPage = 15, array $columns = ['*'])` and returns a bounded first page.
- Adds `illuminate/database` as a runtime dependency (used by the casting layer).
- Aggregation results are stored per collection instance instead of a global `Collection::macro()` — multiple aggregations coexist and results no longer leak across requests in long-lived workers (Octane/queues).
- Boolean clauses (`must`/`should`/`must_not`/`filter`) always render as arrays of clause objects; `multiMatch()` and `matchPhrase()` nest as `bool.must`.
- `count()` sends only the `query` to the `_count` API (previously the full search body, which the endpoint rejects).

### Removed
- **BREAKING** — the deprecated `from` / `to` range operators (`RangeOperator::FROM` / `::TO`); only `gt`/`gte`/`lt`/`lte` are valid in a `range` query.

### Fixed
- `decimal` casts no longer crash on serialization (`toArray()`/`toJson()`).
- `stats()`, `histogram()`, and all query-scoped aggregates now work (aggregation key/macro/bucket detection aligned); numeric histogram bucket keys no longer throw (`Bucket::key()` accepts numeric keys).
- `count()` now honors filters when only a filter clause is set (previously it counted all documents).
- `getAttribute()` no longer errors when a hit has no `_source` (aggregation-only or `_source: false` responses).
- `PaginatedCollection::links()` no longer fatals on empty result sets (returns `total: 0` with empty cursors).
- `save()` no longer mutates shared query-builder state and correctly builds the update body.
- Filters used outside an explicit `asBoolean()` are promoted into a `bool` instead of being silently dropped; chaining same-type boolean clauses produces valid DSL.
- `::fake()` no longer references a test-only class (which caused a fatal class-not-found in non-dev installs).

### Upgrading
See the [v1 → v2 upgrade guide](https://elasticbridge.dev/docs/v2/upgrade-guide). Key steps:
rename `ELASTICSEARCH_*` env vars to `SEARCH_*`, re-publish the config
(`php artisan vendor:publish --tag="elastic-bridge-config" --force`), and update any custom
`ConnectionInterface` implementations and `BridgeBuilder::all()` call sites.

### Notes
- Requires PHP 8.2 / 8.3 and Laravel 10.x–12.x. (Laravel 13 is not yet supported.)
- `TermSetValidator` was renamed to `TermsSetValidator` so it resolves for the `terms_set` term.
