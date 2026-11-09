<p align="center">
  <a href="https://elasticbridge.dev">
    <img src="https://elasticbridge.dev/brand/logo-banner-dark.png" alt="ElasticBridge — an eloquent way to search" width="100%">
  </a>
</p>

# An Eloquent Way To Search.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lacasera/elastic-bridge.svg?style=flat-square)](https://packagist.org/packages/lacasera/elastic-bridge)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/lacasera/elastic-bridge/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/lacasera/elastic-bridge/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/lacasera/elastic-bridge.svg?style=flat-square)](https://packagist.org/packages/lacasera/elastic-bridge)

ElasticBridge allows you to write `Fluent`, `Eloquent` like queries against **Elasticsearch, OpenSearch, and Meilisearch** in your Laravel application.

With ElasticBridge, you can interact with your search indexes as easily as you would with traditional Eloquent models — full-text search, filters, attribute casting, and bulk indexing. Elasticsearch and OpenSearch also support aggregations and multi-index queries.

## Requirements

- PHP 8.2 or 8.3
- Laravel 10.x, 11.x, or 12.x
- Elasticsearch 8.x, OpenSearch 2.x, **or** Meilisearch 1.14+

## Compatibility Matrix

| Laravel Version | PHP Version | Testbench Version | Carbon Version | PHPUnit Version |
|----------------|-------------|-------------------|----------------|-----------------|
| 10.x           | 8.2, 8.3    | 8.*              | ^2.63          | ^10.5           |
| 11.x           | 8.2, 8.3    | 9.*              | ^2.63          | ^10.5           |
| 12.x           | 8.3         | 10.*             | ^3.8.4         | ^11.5.3         |

## Installation

Install the package via Composer:

```bash
composer require lacasera/elastic-bridge
```

## Meilisearch

Set the driver and endpoint in your Laravel `.env`:

```dotenv
SEARCH_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your-api-key
MEILISEARCH_PRIMARY_KEY=id
```

Every document needs a primary key. The bridge's `id` input is stored in the configured Meilisearch primary-key field, including during `bulk()` and `upsert()`. Writes wait for Meilisearch's indexing task to finish; the default timeout is 30 seconds (`MEILISEARCH_TASK_TIMEOUT_MS`).

Configure fields used in filters and sorting in the Meilisearch index settings before querying:

```php
$connection = app(\Lacasera\ElasticBridge\Connection\ConnectionInterface::class);
assert($connection instanceof \Lacasera\ElasticBridge\Connection\MeilisearchConnection);
$index = $connection->getClient()->index('hotel_rooms');

$filterTask = $index->updateFilterableAttributes(['currency', 'price']);
$sortTask = $index->updateSortableAttributes(['price']);
$index->waitForTask($filterTask['taskUid']);
$index->waitForTask($sortTask['taskUid']);
```

```php
HotelRoom::create(['id' => 1, 'name' => 'Harbor', 'currency' => 'USD', 'price' => 120]);

$rooms = HotelRoom::asBoolean()
    ->mustMatch('name', 'harbor')
    ->filterByTerm('currency', 'USD')
    ->orderBy('price')
    ->simplePaginate(15, 0)
    ->get();
```

The Meilisearch driver supports a single text clause with term/range/exists filters, sorting, field selection, `find()`, `count()`, `create()`, `save()`, `bulk()`, and `upsert()`. `all()` uses offset pagination. `cursorPaginate()`, aggregations, Elasticsearch scripts (`increment()`/`decrement()`), geo DSL, fuzzy/regex/prefix DSL, and wildcard or multi-index reads are unavailable and raise an exception. Filtered `count()` uses Meilisearch's `totalHits`, which is subject to the index's `pagination.maxTotalHits` setting.

## Overview
```bash
    php artisan make:bridge HotelRoom
```

```php
<?php 
declare(strict_types=1);

namespace App\Bridges;

use Lacasera\ElasticBridge\ElasticBridge;

class HotelRoom extends ElasticBridge 
{
       
}
```

```php
<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Bridges\HotelRoom;
use App\Requests\SearhRequest;

class SearchController extends  Controller
{
    public function __invoke(SearhRequest $request)
    {
        $rooms = HotelRoom::asBoolean()
            ->matchAll()
            ->orderBy('price', 'DESC'),
            ->filterByTerm('code', 'usd')
            ->filterByRange('price', 20, 'gte')
            ->filterByRange('price', 500, 'lte')
            ->cursorPaginate(50)
            ->get(['price']);
            
            
        return response()->json([
            'data' => $rooms
        ]);
    }
}
```

# [Documentation](https://elasticbridge.dev)

## AI-assisted development

ElasticBridge is built to be first-class for AI coding agents:

- **Laravel Boost skill & guidelines.** The package ships an
  [`elastic-bridge-development`](resources/boost/skills/elastic-bridge-development/SKILL.md)
  skill and [core guidelines](resources/boost/guidelines/core.blade.php). If your project uses
  [Laravel Boost](https://laravel.com/docs/boost), run `php artisan boost:install` and your agent
  automatically gets ElasticBridge-specific guidance.
- **LLM-friendly docs.** The documentation is published in the
  [`llms.txt`](https://llmstxt.org) format at
  [elasticbridge.dev/llms.txt](https://elasticbridge.dev/llms.txt) (index) and
  [elasticbridge.dev/llms-full.txt](https://elasticbridge.dev/llms-full.txt) (full text) so LLMs
  can read the whole API in one fetch.

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Agyenim Boateng](https://github.com/lacasera)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


TODO 
[] Add logging for queries and response...
