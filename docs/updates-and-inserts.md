## #Updates and Inserts

### #Inserts
When using elastic bridge, not only can we retrieve documents from our elastic index, we can also 
index new documents. Elastic bridge makes this super easy. 
To index a new document, just create an instance of the bridge class, 
set your attributes and call the save method on the bridge index. 

```php

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Bridges\Log;
use Illuminate\Http\Request;


class LogController extends Controller 
{
    /**
     * Index a new log.
     */
    public function store(Request $request): RedirectResponse
    {
        $log = new Log();
        
        $log->status = $request->status_code;
        $log->message = $request->message;
        
        $log->save();
        
        return response()->json(['message' => "log indexed successfully"], 201);
    }
}
```

!!! tip 
    Just as like elastic search, whenever you set the `id` on a `new bridge` instance, it will be set as the `_id` of the document

Alternatively you can use the `create` method to "index" a new document. both the `save` and `create` methods returns `booleans`. 

```php
<?php

use App\Bridges\Log;

Log::create([
    'status' => 500,
    'message' => 'this is a log message'
])

```

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
