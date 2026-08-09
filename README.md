<p align="center">
  <a href="https://elasticbridge.dev">
    <img src="https://elasticbridge.dev/brand/logo-banner-dark.png" alt="ElasticBridge — an eloquent way to search" width="100%">
  </a>
</p>

# An Eloquent Way To Search.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lacasera/elastic-bridge.svg?style=flat-square)](https://packagist.org/packages/lacasera/elastic-bridge)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/lacasera/elastic-bridge/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/lacasera/elastic-bridge/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/lacasera/elastic-bridge/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/lacasera/elastic-bridge/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/lacasera/elastic-bridge.svg?style=flat-square)](https://packagist.org/packages/lacasera/elastic-bridge)

ElasticBridge allows you to write `Fluent`, `Eloquent` like queries against **Elasticsearch and OpenSearch** in your Laravel application.

With ElasticBridge, you can interact with your search indexes as easily as you would with traditional Eloquent models — full-text search, filters, aggregations, attribute casting, bulk indexing, and multi-index queries — bringing the power of Elasticsearch/OpenSearch into the Laravel ecosystem with no effort.

## Requirements

- PHP 8.2 or 8.3
- Laravel 10.x, 11.x, or 12.x
- Elasticsearch 8.x **or** OpenSearch 2.x

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
