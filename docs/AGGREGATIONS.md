# ElasticBridge Fluent Aggregations

The ElasticBridge package now supports complex aggregations through a fluent API that makes it easy to build nested aggregations.

## Basic Usage

### Your Original Query - Now Fluent!

Instead of using the raw query structure, you can now write your complex date histogram aggregation like this:

```php
<?php

use App\Bridges\MetalPriceBridge; // Your bridge class

$results = MetalPriceBridge::query()
    ->asBoolean()
    ->filterByTerm('symbol', $symbol)
    ->filterByRange('timestamp', $startDate, 'gte')
    ->filterByRange('timestamp', 'now', 'lte') 
    ->size(0) // Don't return documents, only aggregations
    ->dateHistogram('price_over_time', 'timestamp', $interval)
        ->avg('avg_price', 'price_eur')
        ->end()
    ->get();

// Access the results
$priceOverTimeData = $results->priceOverTime();
foreach ($priceOverTimeData['buckets'] as $bucket) {
    $timestamp = $bucket['key_as_string'];
    $averagePrice = $bucket['avg_price']['value'];
    echo "Date: {$timestamp}, Avg Price: {$averagePrice}\n";
}
```

## Available Aggregation Methods

### Date Histogram
```php
$results = Bridge::query()
    ->dateHistogram('sales_over_time', 'date', '1d')
        ->avg('avg_amount', 'amount')
        ->sum('total_sales', 'amount')
        ->count('transaction_count', 'id')
        ->end()
    ->get();
```

### Terms Aggregation
```php
$results = Bridge::query()
    ->termsAgg('top_categories', 'category', 10)
        ->avg('avg_price', 'price')
        ->max('max_price', 'price')
        ->end()
    ->get();
```

### Histogram (Numeric)
```php
$results = Bridge::query()
    ->histogramAgg('price_ranges', 'price', 100)
        ->count('item_count', 'id')
        ->end()
    ->get();
```

### Range Aggregation
```php
$results = Bridge::query()
    ->rangeAgg('price_brackets', 'price', [
        ['to' => 50],
        ['from' => 50, 'to' => 100],
        ['from' => 100]
    ])
        ->sum('total_revenue', 'amount')
        ->end()
    ->get();
```

### Metric Aggregations
```php
$results = Bridge::query()
    ->metric('avg_price', 'avg', 'price')
    ->metric('total_sales', 'sum', 'amount')
    ->metric('price_stats', 'stats', 'price')
    ->get();
```

## Sub-Aggregations

All aggregation builders support these sub-aggregation methods:

- `avg(name, field)` - Average value
- `sum(name, field)` - Sum of values  
- `min(name, field)` - Minimum value
- `max(name, field)` - Maximum value
- `count(name, field)` - Count of values
- `stats(name, field)` - Full statistics
- `terms(name, field, size)` - Terms breakdown
- `dateHistogram(name, field, interval)` - Date histogram breakdown

## Complex Nested Example

```php
$results = MetalPriceBridge::query()
    ->asBoolean()
    ->filterByRange('timestamp', '2023-01-01', 'gte')
    ->size(0)
    ->dateHistogram('daily_prices', 'timestamp', '1d')
        ->avg('avg_price_eur', 'price_eur')
        ->avg('avg_price_usd', 'price_usd')
        ->terms('top_symbols', 'symbol', 5)
        ->customSubAgg('price_stats', [
            'stats' => ['field' => 'price_eur']
        ])
        ->end()
    ->termsAgg('symbol_breakdown', 'symbol', 10)
        ->dateHistogram('symbol_daily', 'timestamp', '1d')
        ->avg('symbol_avg_price', 'price_eur')
        ->end()
    ->get();

// Access nested results
$dailyPrices = $results->dailyPrices();
foreach ($dailyPrices['buckets'] as $dayBucket) {
    echo "Date: " . $dayBucket['key_as_string'] . "\n";
    echo "Avg EUR: " . $dayBucket['avg_price_eur']['value'] . "\n";
    echo "Top Symbols:\n";
    foreach ($dayBucket['top_symbols']['buckets'] as $symbolBucket) {
        echo "  - " . $symbolBucket['key'] . ": " . $symbolBucket['doc_count'] . " records\n";
    }
}
```

## Configuration Options

### Date Histogram Options
```php
->dateHistogram('sales', 'date', '1d')
    ->options([
        'time_zone' => 'Europe/London',
        'format' => 'yyyy-MM-dd',
        'offset' => '+1h'
    ])
    ->minDocCount(1)
    ->end()
```

### Terms Aggregation Options  
```php
->termsAgg('categories', 'category')
    ->size(20)
    ->missing('Unknown')
    ->options([
        'order' => ['_count' => 'desc'],
        'include' => ['electronics', 'books']
    ])
    ->end()
```

## Migration from Raw Queries

### Before (Raw Query)
```php
$results = Bridge::query()
    ->raw([
        'size' => 0,
        'aggs' => [
            'price_over_time' => [
                'date_histogram' => [
                    'field' => 'timestamp',
                    'calendar_interval' => '1d'
                ],
                'aggs' => [
                    'avg_price' => [
                        'avg' => ['field' => 'price_eur']
                    ]
                ]
            ]
        ]
    ])
    ->get();
```

### After (Fluent API)
```php
$results = Bridge::query()
    ->size(0)
    ->dateHistogram('price_over_time', 'timestamp', '1d')
        ->avg('avg_price', 'price_eur')
        ->end()
    ->get();
```

The fluent API is more readable, type-safe, and provides better IDE support while generating the same Elasticsearch queries.