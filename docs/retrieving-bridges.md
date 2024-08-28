## # Retrieving Data
[# Introduction](#introduction)

[# Building Queries](#queries)

[# Aggregates](#aggregates)

[# Ordering, Limit and Offset](#ordering-sorting-limit-and-offset)
    
- [# Ordering](#ordering)

- [# Limit and Offset](#limit-and-offset)
    
- [# Sorting](#sorting) 


<a name="introduction"></a>
After creating a bridge and its associated elastic search index, you are ready to start retrieving data from your elasticsearch. 
You can think of each bridge as a powerful [query builder](builder.md) allowing you to fluently query the elasticsearch index associated with the bridge. 

The `all` method will retrieve all documents from the bridge's associated elasticsearch index:
```php
<?php
declare(strict_types=1);

namespace App\Bridges;

use Lacasera\ElasticBridge\ElasticBridge;

class HotelRoom extends ElasticBridge 
{
    protected $index = 'hotel-room';   
}

foreach (HotelRoom::all() as $hotelRoom) {
    echo $hotelRoom->price;
}
```

<a name="queries"></a>
## # Building Queries

Each bridge serves as a [query builder](builder.md) that allows you to add additional constraints to queries and then invoke the `get` method to retrieve the results. 

```php
use App\Bridges\HotelRoom;

$rooms = HotelRoom::asBoolean()
    ->mustMatch('advertiser', 'booking.com')
    ->orderBy('price')
    ->take(10)
    ->get();
```

<a name="aggregates"></a>
## #Retrieving Single Bridges / Aggregates

In addition to retrieving all documents matching a given query, you may also retrieve single document using the find, first method. Instead of returning a collection of bridges, the find method returns a single bridge instance:

```php
use App\Bridges\HotelRoom;

$room = HotelRoom::find(1);
```

## # Retrieving Aggregates
When interacting with bridges you may also use the `count`, `sum`, `max`, `min`, `avg` aggregate methods provided by the query builder. As you might expect, these methods return a scalar value instead of a bridge instance.

```php
use App\Bridges\HotelRoom;

// count all documents in the given index
echo HotelRoom::count(); 

// find the minimum price in the documents for the given index
echo HotelRoom::min('price'); 

// find the maximum price in the documents for the given index
echo HotelRoom::max('price'); 

// find the avg price in the documents for the given index
echo HotelRoom::avg('price'); 

// sum the prices in the documents for the given index
echo HotelRoom::sum('price'); 


// find the sum of the price for the given query
echo HotelRoom::asBoolean()
    ->mustMatch('advertiser', 'booking.com') 
    ->sum('price');
```

---
**NOTE**

As mentioned, these aggregate methods returns scalar values. 

you can use the `withAggregate` method on a query to return both the collection of bridges and the scalar value of and aggregate

---


```php
use App\Bridges\HotelRoom;

// collection of bridges.
$rooms = HotelRoom::asBoolean()
     ->mustMatch('advertiser', 'booking.com') 
     ->withAggregate('min', 'price')
     ->get();

foreach ($rooms as $room) {
    echo $room->price;
}

// access the aggregate value via a method composed of the `aggregate type` and the `field name` as a `camel case`.
echo $room->minPrice();


$rooms = HotelRoom::asBoolean()
     ->mustMatch('advertiser', 'booking.com') 
     ->withAggregate('avg', 'price')
     ->get();

echo $rooms->avgPrice();
```

## # Ordering, Limit and Offset
<a name="ordering-sorting-limit-and-offset"></a>

<a name="ordering"></a>
#### # Ordering
The orderBy method allows you to sort the results of the query by a given column. The first argument accepted by the orderBy method should be the column you wish to sort by, while the second argument determines the direction of the sort and may be either `asc` or `desc`:

```php
use App\Bridges\HotelRoom;

$rooms = HotelRoom::asBoolean()
    ->mustMatch('advertiser', 'booking.com') 
    ->orderBy('price', 'desc')
    ->get();
```

To sort by multiple columns, you may simply invoke orderBy as many times as necessary:

```php
use App\Bridges\HotelRoom;

$rooms = HotelRoom::asBoolean()
    ->mustMatch('advertiser', 'booking.com') 
    ->orderBy('price', 'desc')
    ->orderBy('created', 'asc')
    ->get();
```

<a name="limit-and-offset"></a>
#### # Limit and Offset

#### # The `skip` and `take` Methods
You may use the `skip` and `take` methods to limit the number of results returned from the query or to `skip` a given number of results in the query:

```php
use App\Bridges\HotelRoom;

$rooms = HotelRoom::asBoolean()
    ->mustMatch('advertiser', 'booking.com')
    ->skip(10)
    ->take(5)
    ->get();
```

Alternatively, you may use the `limit` and `offset` methods. These methods are functionally equivalent to the `take` and `skip` methods, respectively:

```php
use App\Bridges\HotelRoom;

$rooms = HotelRoom::asBoolean()
    ->mustMatch('advertiser', 'booking.com')
    ->offset(5)
    ->limt(10)
    ->get();
```
