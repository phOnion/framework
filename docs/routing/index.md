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
            'headers' => [ // NEW! Optional, a list of headers to enforce to the route's response
                'x-random-stuff' => true,
                'x-more-random-stuff' => false,
            ],
            'request_handler' => 'RequestHandlerKey', // NEW! Optional, instance to directly attach to the route
            'middleware' => [] // Required. List of keys for the middleware of the route, omitted if `request_handler` is present
        ]
    ]
];
```

If a custom `class` is provided, it will be used for managing the route. That way a custom dispatch strategy could be used
and/or changes of pattern syntax and what not. As for `headers` it is intended to validate required headers to a response of the route
on success. Things like explicitly setting HTTP Cache value for example can be added here and remove the need for adding
data to the logic or implement some sort of a redirect route.

## Definition

The full route structure looks like this:

```php
[
    'name' => 'alias-name', // Optional
    'pattern' => '/products/{product}', // Required
    'class' => SomeRoute::class, // Optional
    'request_handler' => SomeRequestHandler::class, // Optional
    'middleware' => [ // Required, if `request_handler` is not present
        // list of middleware keys to resolve
    ],
    'methods' => [ // Optional
        // list of HTTP methods
    ],
    'headers' => [ // Optional
        'x-header-name' => true // Definition of a route header
    ]
]
```

- `'name'` - An alias for a route useful if resolving pattern to route
- `'pattern'` - The pattern of the route
- `'request_handler'` - A request handler to use, bypassing the use of middleware (Useful for modules)
- `'middleware'` - A list of middleware keys that handle the route. Ignored if `request_handler` is present
- `'class'` - A class which will handle the route (Defaults to `RegexRoute`)
- `'methods'` - A list of HTTP methods to restrict the route to. Useful for early termination
- `'headers'` - A list of headers to enforce in the request and mark them as required or not

Route headers could be handy when needing to enforce a rule on a given route.
For example require the requests to provide an `Authorization` header will
have `'Authorization' => true` enforcing it and if it is not provided non of the
middleware stack will be triggered and the app will return early. Making you
not have to check if the header is present all over the place. This can be of a
huge benefit if a module is entirely user protected to restrict access to it.

## Route types

### 1. `StaticRoute`

Handles the request path as is, without doing anything behind the scenes and triggers it's stack directly.

### 2. `RegexRoute`

This route is the default one used by the default application factory.
As the name implies it is a regex-based routing that supports optional
parameters and parameter validation in it's syntax. To access route
parameters you must use `$request->getAttribute('parameterName');` from
the incoming request.

#### Pattern Syntax

In order to define a parameter you must surround the "key" with square
braces `{parameter}` and in the common scenario you are done. For example the
following pattern has a parameter named `id`, which is required: `/products/{id}`.

#### Optional Params

Optional parameters use the same syntax, you just have to add question makr
`?` ath end of the param and you are good, like `{parameter}?`.
Updating the same example as before with making the `id` parameter optional:
`/product/{id}?`. Note that the `/` is before the parameter name and that will
make it required. If you want to avoid having that, you can surround your parameter
part of an optional group by doing this: `/product{/{id}}?` this will
indicate that the trailing slash is required only when the has a value
for `{id}` provided.

#### Parameter Constraints

In order to put constraints on a route we can use `:` after the parameter
name to denote that what is after it and before the `}` is a pattern.
For example to enforce our `{id}` parameter from above to accept only integers
we can define it as such: `/product{/{id:\d+}}?` that way if it is present
it will have to be of 1+ numeric characters.
