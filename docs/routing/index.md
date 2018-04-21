# Introduction

Since the `Router` class has been removed in the 2.0 release the concept of routing
has been moved to the application class + the dispatching has been moved inside the
Route classes hance making them self-contained and responsible for their own execution.

Thus the `Application` class has absolutely no knowledge of how the route is dispatched
it just "asks" it if it matches the current request and if it is - handles the sending
of the response to the client.

The route definition structure has not changed much:

```php
return [
    'routes' => [
        [
            'name' => 'home', // Optional
            'pattern' => '/', // Required. Not changed at all
            'methods' => [], // Optional
            'class' => \Onion\Framework\Router\StaticRoute::class, // New! Optional, what class to use when building the route
            'headers' => [ // NEW! Optional, a list of headers to add automatically to the route's response (supported only by internal routes)
                'x-random-stuff' => '123',
                'x-more-random-stuff' => ['abc', 'test'],
            ],
            'middleware' => [] // Required. List of keys for the middleware of the route
        ]
    ]
]
```

If a custom `class` is provided, it will be used for managing the route. That way a custom dispatch strategy could be used
and/or changes of pattern syntax and what not. As for `headers` it is intended to append headers to a response of the route
on success. Things like explicitly setting HTTP Cache value for example can be added here and remove the need for adding
data to the logic or implement some sort of a redirect route.
