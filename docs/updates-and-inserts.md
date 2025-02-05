## #Updates and Inserts

### #Inserts

### #Updates

The `save` method may be used to update bridges that already exist in elasticsearch. 
To Update a bridge, you should first retrieve it and set the attributes you wish to update and then call the `save`
method. 

```php
use App\Bridges\HotelRoom;

$room = HotelRoom::find(1);

$room->price = 50;

$room->save();

```

In addition, you can use the `increment` or `decrement` method to increase or decrease numeric fields for a bridge. 
Both these methods takes an optional second argument called `counter` with defaults to `1`

```php

use App\Bridges\HotelRoom;

$room = HotelRoom::find(1);

echo $room->price; // 50

// increase price by 3
$room->increment('price', 3)

echo $room->price; // 53

// decrease price by 1
$room->decrement('price');

echo $room->price; //52
```
