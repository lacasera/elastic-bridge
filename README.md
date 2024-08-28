# An eloquent way to search

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lacasera/elastic-bridge.svg?style=flat-square)](https://packagist.org/packages/lacasera/elastic-bridge)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/lacasera/elastic-bridge/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/lacasera/elastic-bridge/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/lacasera/elastic-bridge/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/lacasera/elastic-bridge/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/lacasera/elastic-bridge.svg?style=flat-square)](https://packagist.org/packages/lacasera/elastic-bridge)

ElasticBridge allows you to seamlessly "Bridge" the gap between Laravel's Eloquent models and Elasticsearch. 

With ElasticBridge, developers can interact with Elasticsearch indexes as easily as they would with traditional Eloquent models, bringing the power of Elasticsearch into the Laravel ecosystem with minimal effort.

This package simplifies the complexity of Elasticsearch queries, allowing you to execute powerful search operations while maintaining the elegance and familiarity of Laravel’s syntax.

Whether you're building complex search features or managing large datasets, ElasticBridge makes it easy to leverage Elasticsearch’s capabilities directly within your Laravel application.

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

[Usage](doc)

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

