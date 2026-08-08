# Changelog

All notable changes to `elastic-bridge` will be documented in this file.

## [Unreleased]

### Added
- Laravel 12.x support
- PHP 8.3 support
- Carbon 3.8.4+ support for Laravel 12
- Orchestra Testbench 10.x support for Laravel 12
- Bulk insert and upsert via `Model::bulk([...])` and `Model::upsert([...])` — automatic chunking, a configurable record cap (`bulk.max` / `bulk.chunk_size`), and a `BulkResult` DTO (`successful()`, `failed()`, `count()`, `total()`, `hasErrors()`) reporting per-item outcomes
- Query validation layer is now active: `bool`, `match`, and `terms_set` term-level queries are validated when built (`QueryValidator` wired into `QueryBuilder::getPayload()`)
- Order direction (`orderBy`) and range operators (`filterByRange`, `range`) are now validated against `OrderOperator`/`RangeOperator`, throwing `InvalidQuery` on invalid input
- `Lacasera\ElasticBridge\Testing\FakeConnection` — a runtime-safe fake connection backing `::fake()`

### Changed
- Updated GitHub Actions workflow to dynamically handle Laravel 12 dependencies
- Updated composer.json to support Laravel 10.x, 11.x, and 12.x
- Updated phpunit.xml.dist schema to PHPUnit 11.5 (backward compatible with PHPUnit 10.5)
- Improved version constraints for better compatibility across Laravel versions
- Base package uses PHPStan 1.x (compatible with Rector), CI upgrades to PHPStan 2.1+ for Laravel 12 testing
- Aggregation results are now stored per collection instance instead of via a global `Collection::macro()`. Results are still retrieved the same way (`$results->priceStats()`), but multiple aggregations on one response now coexist and results no longer leak across requests in long-lived workers (Octane, queues)
- `all()` now returns a bounded first page (default `PAGINATION_SIZE`); the two divergent `all()` implementations were unified into one, removing the unbounded page size that could exceed Elasticsearch's `max_result_window`
- `BoolValidator` now accepts all standard boolean clauses (`must`, `should`, `must_not`, `filter`, `minimum_should_match`, `boost`) and requires at least one
- Boolean occupant clauses (`must`, `should`, `must_not`, `filter`) are now always emitted as arrays of clause objects (e.g. `must: [{…}]`), producing valid DSL for both single and multiple clauses
- `multiMatch()` and `matchPhrase()` are now nested as `bool.must` clauses (they have no term-level of their own), producing valid DSL instead of an invalid top-level sibling key
- `count()` now sends only the `query` to the `_count` API (previously the full search body, including `sort`/`size`/`aggs`/`_source`, which the endpoint rejects)

### Removed
- Deprecated `from`/`to` range operators (`RangeOperator::FROM`/`::TO`); only `gt`/`gte`/`lt`/`lte` are valid in an Elasticsearch `range` query (7.x+)

### Fixed
- `getAttribute()` no longer errors when a hit has no `_source` (aggregation-only or `_source: false` responses)
- `PaginatedCollection::links()` no longer fatals on empty result sets; returns `total: 0` with empty `previous`/`next` sort cursors
- `::fake()` no longer references a test-only class, which caused a fatal class-not-found in non-dev installs
- `save()` no longer mutates shared query builder state when checking for an existing record
- Chaining two clauses of the same boolean type (e.g. `mustMatch()->mustMatch()`) no longer produces malformed DSL that Elasticsearch rejects
- Filters (`filterByTerm`/`filterByRange`/`filterByGeo*`) are no longer silently dropped when used outside an explicit `asBoolean()` context; the query is promoted into a `bool` with the filters attached

### Notes
- For Laravel 12 development, PHPUnit 11.5.3+, Larastan 3.0, and PHPStan 2.1+ are required
- CI/CD pipeline automatically installs correct versions based on Laravel version being tested
- Base package remains compatible with Laravel 10 and 11 out of the box
- `TermSetValidator` was renamed to `TermsSetValidator` so it resolves for the `terms_set` term
- The internal `tests/MockElasticConnection` was replaced by `Lacasera\ElasticBridge\Testing\FakeConnection`; aggregation results are no longer registered as global collection macros

