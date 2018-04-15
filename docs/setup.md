# Introduction

In this section we'll get through a simple hello world app to help you
get a grip around the concepts of the framework as well as how to
set it up and work on it.

1st thing is the index file, it is the entry point for the application.
The application consists of middleware, which if you are not familiar
with you should think of as onion's layers (now you get where the name
comes from). See:

- [PSR-15 Specification](https://github.com/php-fig/fig-standards/tree/master/proposed/http-middleware)

- [Why Care About PHP Middleware?](https://philsturgeon.uk/php/2016/05/31/why-care-about-php-middleware/) by *Phil Phil Sturgeon*

Choosing single pass ensures no funny business will be going inside the
middleware you use, and you can be sure that the response you return
will be consistently handled, and you will not have to keep track of
the double-pass response object, that may or may not have some specific
headers set by any of the middleware called before your "controller".

## Installation

So with that being said, lets jump right in with the installation
and setup of the "hello world" project:

1. `composer require onion/framework:2.0`
2. Create the public directory one that will be exposed by the web server, referred to as `public` in this example
3. In it, create a file named `index.php` and inside of it paste the code below.
4. To test it after you copied and pasted the code run the following:
 `php -S localhost:12345 -t public/` inside the projects directory,
 that will start the PHP built-in server and will make the application
  code accessible on: [localhost:12345](http://localhost:12345)

```php
    <?php
    declare(strict_types=1);
    require_once __DIR__ . '/../vendor/autoload.php';
    use Onion\Framework;

    $container = new Framework\Dependency\Container([
        'factories' => [
            Framework\Application\Application::class => // Takes care of routing
                Framework\Application\Factory\ApplicationFactory::class,
            \Psr\Http\Server\RequestHandlerInterface::class => // Necessary for error handling
                Framework\Http\Middleware\Factory\RequestHandlerFactory::class
        ],
        'invokables' => [
            // Optional, this is the default behavior,
            // change if a different response template should be used
            \Psr\Http\Message\ResponseInterface::class =>
                \Guzzle\Psr7\Response::class
        ],
        'routes' => [ // Application routes
            [ // A route :D
                'pattern' => '/',
                'middleware' => [
                    // Add your route middleware here
                ]
            ]
        ],
        'middleware' => [
            // Application-level middleware should go here
        ]
    ]);

    $app = $container->get(Framework\Application\Application::class);
    $app->run(GuzzleHttp\Psr7\ServerRequest::fromGlobals()); // Or another request factory
```

*Note You need to implement your route middleware and define it here otherwise an empty response will be returned*

After accessing the app you should be presented with whatever output you expect to see. That is it

---

## Middleware

There are 2 types of middleware supported atm, application level & route level.
Currently the handling of application level middleware is achieved in 2 ways

1. If a route is triggered the application middleware is "attached" infront of
 the route middleware and the execution happens transparently for the route and
 the prepend logic is located inside the `ApplicationFactory` so if another
 factory is used to build the route stack, that should be taken in to account.
2. If there is an exception (which is what happens when no route is found as well
 as from the application code) a generic `RequestHandler` is built with only
 the global middleware and the thrown exception is added to the request attributes
 as `error` and `exception`.

In an ideal scenario that should not be a huge issue when route error occurs
 and the common stack is triggered again, although it should be taken in to
 account for the purposes of request logging, etc. as it may result in duplicate
 entries for the same request. (But you really should handle your errors :) )

## Routing

The full route structure looks like this:

```php
[
    'name' => 'alias-name', // Optional
    'pattern' => '/products/[product]', // Required
    'class' => SomeRoute::class, // Optional
    'middleware' => [ // Required
        // list of middleware keys to resolve
    ],
    'methods' => [ // Optional
        // list of HTTP methods
    ],
    'headers' => [ // Optional
        'x-header-name' => ['header-value-for-{product}?page={page:1+1}'] // Definition of a route header
    ]
]
```

- `'name'` - An alias for a route useful if resolving pattern to route
- `'pattern'` - The pattern of the route
- `'middleware'` - A list of middleware keys that handle the route
- `'class'` - A class which will handle the route (Defaults to `RegexRoute`)
- `'methods'` - A list of HTTP methods to restrict the route to. Useful for early termination
- `'headers'` - A list of headers to add to the generated response. Supports route and query
 params as well as `+` and `-` expressions like `{numericParam+1}` will increment the value of
 `numericParam` by 1

 The syntax for header templating is `{<paramName>[:<defaultValue>][(+|-)<number>]}`

- `<paramName>` - is the name of the parameter either from route or query. Pattern `[a-zA-Z0-9_-]+`
- `:<default>` - is optional and if the parameter is not present the value after the `:` will be used. Pattern `[a-zA-Z0-9_-]+`
 or `\d+`, depending on the expression
- `(+|-)<number>` - increment or decrement the value of `<paramName>` by `<number>`. Pattern `\d+`, if `<default>` is provided
 and it contains a anything else than a number it will not evaluate the expression


Route headers could be handy when providing `Link` headers for navigation or pointing
to sub-resources, with the supported expressions pagination links can also be provided
and possibly other useful cases.
