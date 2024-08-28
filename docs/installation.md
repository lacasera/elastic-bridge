## #Installation

You can install the package via composer:

```bash
composer require lacasera/elastic-bridge
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="elastic-bridge-config"
```

This is the contents of the published config file:

```php
return [

    /**
     * elastic host
     */
    'host' => [env('ELASTICSEARCH_HOST', 'https://localhost:9200')],

    /**
     * elastic username
     */
    'username' => env('ELASTICSEARCH_USERNAME', 'elastic'),

    /**
     * elastic password
     */
    'password' => env('ELASTICSEARCH_PASSWORD', null),

    /**
     * path to certificate file generated when installing elastic
     */
    'certificate_path' => storage_path(),

    /**
     * where should bridge files be located
     */
    'namespace' => 'App\\Bridges',
];

```
