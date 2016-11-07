## Getting started

In this section we'll get through a simple hello world app to help you
get a grip around the concepts of the framework as well as how to 
set it up and work on it.

1st thing is the index file, it is the entry point for the application.
The application consists of middleware, which if you are not familiar 
with you should think of as onion's layers (now you get where the name 
comes from). See:

 - [PSR-15 Specification](https://github.com/php-fig/fig-standards/tree/master/proposed/http-middleware)

 - [Why Care About PHP Middleware?](https://philsturgeon.uk/php/2016/05/31/why-care-about-php-middleware/) by *Phil Phil Sturgeon*
 
Chosing single pass ensures no funny business will be going inside the 
middleware you use, and you can be sure that the response you return 
will be consistently handled, and you will not have to keep track of
the double-pass response object, that may or may not have some specific 
headers set by any of the middleware called before your "controller".

## Installation

So with that being said, lets jump right in with the installation 
and setup of the "hello world" project:

1. `composer require onion/framework:1.0.0-beta`
2. Create the public directory one that will be exposed by the web 
server, referred to as `public` in this example
3. In it, create a file named `index.php` and inside of it paste the 
following:

    ```
    <?php
    declare(strict_types=1);
    require_once __DIR__ . '/../vendor/autoload.php';
    use Onion\Framework;
    
    $container = new Framework\Dependency\Container([]);
    
    $app = $container->get(Framework\Application\Application::class);
    $app->run(
        $container->get(\Psr\Http\Message\ServerRequestInterface::class)
    );
    ```
    
    This is the minimal required code in order to set up the application 
    entry point.

4. To test it run the following: `php -S localhost:12345 -t public/` 
inside the projects directory, that will start the PHP built-in server
and will make the application code accessible on: 
[localhost:12345](http://localhost:12345)

--- 

## Dependencies

Now if you open the link with your browser, you will see there is an 
exception being thrown, similar to (if no the same as):

> Uncaught Onion\Framework\Dependency\Exception\ContainerErrorException: Unable to find match for type: "Interop\Http\Middleware\DelegateInterface". Consider using a factory
> ...

What this means is that the container is unable to resolve the 
dependency of `DelegateInterface`, this sucks big time, now before you 
start digging in the code, hold on for a sec and lets see what happens
in our `index.php` file.

1. `L2-L4` - We declare that we will use strict types, require the 
composer autoloader and localize the namespace (a bit shorter to write)

2. `L6` - We initialize the DI container without any registered 
dependencies (pay attention to the empty array passed as argument), 
which will force the container to use reflection in order to look-up 
dependencies recursively as deep as it can go.

3. `L8` - We attempt to retrieve the `Application` class from the 
container (This is where the exception gets thrown)

4. `L9-L11` - We attempt to run the application by passing it the 
current request object, again retrieved from the container


In order to learn more about the `DelegateInterface` make sure you read 
the PSR-15 spec. Now in the implementation the Application object 
receives 2 arguments in it's constructor:
  
  1. `DelegateInterface` - Is a delegate which has all of the global 
  middleware chained inside it.
  2. `EmitterInterface` - Is responsible for emitting our response 
  "on the way out"™, which is either one of the implementations 
  available inside `zendframework/zend-diactoros` or your own 
  implementation.

The reason that the class is bound to interfaces instead of concrete 
classes is by design and I highly encourage you to work your code around
that mindset, since this enforces the 
[L](https://en.wikipedia.org/wiki/Liskov_substitution_principle) from 
[SOLID](https://en.wikipedia.org/wiki/SOLID_(object-oriented_design))

Now let's update our index to include the `DelegateInterface` and 
`EmitterInterface` in a dependency mapping so that our container knows
what to return when we need any of those dependencies.

```
// Replace the empty array argument for container with the following

[
    'invokables' => [
        Zend\Diactoros\Response\EmitterInterface::class => 
            Zend\Diactoros\Response\SapiEmitter::class
    ],
    'factories' => [
        Interop\Http\Middleware\DelegateInterface::class => 
            Framework\Application\Factory\GlobalDelegateFactory::class
    ]
]
```

---

## Middleware

Now if you run the application you will notice that there is an entierly
different error, about missing `middleware` key.

> Uncaught Onion\Framework\Dependency\Exception\UnknownDependency: Unable to resolve "middleware"

This is because the `GlobalDelegateFactory` expects to see `middleware`
key inside our container and use it in order to build the necessary call
stack. Lets add it, but leave it empty. Add `'middleware' => []` after
the factory definition in the top level array.

> Return value of Onion\Framework\Application\Factory\GlobalDelegateFactory::build() must be an instance of Interop\Http\Middleware\DelegateInterface, null returned

Is pretty self explanatory, but why you ask? Well, the factory needs at 
least one entry inside the `middleware` in order to build a 
`DelegateInterface` with it, just keep in mind it MUST be an instance of 
either `Interop\Http\Middleware\MiddlewareInterface` or
`Interop\Http\Middleware\ServerMiddlewareInterface` and since we are 
building a server application (not a HTTP client) we will pass in an 
argument that is vital to any HTTP application - a router.
But to avoid the error steps above, I will directly tell you that you 
have to define those in order to get it going. Update the array to look 
like the following:
```
[
    'invokables' => [
        Zend\Diactoros\Response\EmitterInterface::class =>
            Zend\Diactoros\Response\SapiEmitter::class,
        Framework\Router\Interfaces\ParserInterface::class =>
            Framework\Router\Parsers\Flat::class,
        Framework\Router\Interfaces\MatcherInterface::class =>
            Framework\Router\Matchers\Strict::class
    ],
    'factories' => [
        Interop\Http\Middleware\DelegateInterface::class =>
            Framework\Application\Factory\GlobalDelegateFactory::class,
        Framework\Router\Interfaces\RouterInterface::class =>
            Framework\Router\Factory\RouterFactory::class,
        Psr\Http\Message\ServerRequestInterface::class =>
            Framework\Http\Factory\ServerRequestFactory::class
    ],
    'middleware' => [
        Framework\Router\Interfaces\RouterInterface::class
    ],
    'routes' => []
]
```

---

## Routing

Since our router implements the `ServerMiddlewareInterface` we can pass
it without any other boilerplate to the middleware section and the
other 2 dependencies inside the invokables section are for making it 
work.

 - `Framework\Router\Interfaces\ParserInterface` is responsible for 
 translating the route definitions to mathcer understandable string
 
 - `Framework\Router\Interfaces\MatcherInterface` is the one performing
 the matching in order to see if the current request URI matches a route
 
In this case we are using the flat matcher, which returns the pattern as
is and the strict matcher that performs `a === b` kind of checks.

---

Now if we refresh the page we will se that there will be an exception:

> Uncaught Onion\Framework\Router\Exceptions\NotFoundException: No route available to handle "/"

Now lets create our first controller, add autoloading for the 
`Application` namespace to composer and inside 
`src/Controllers/DummyController.php` paste the following:
```
<?php
declare(strict_types=1);

namespace App\Controllers;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\TextResponse;

class DummyController implements ServerMiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate = null)
    {
        return new TextResponse('Hello, World!');
    }
}
```

Now that you are done, lets add the controller to the `routes`:
```
// .. other definitions
'routes' => [
  [
    'pattern' => '/',
    'middleware' => [
        App\Controllers\DummyMiddleware::class
    ]
  ]
]
```
 - **FQCN** - *Fully Qualified Class Name*


Now if all went well you should not see any further errors when you 
refresh the page, but rather the text 'Hello, World!' should be 
displayed and the response should be sent with 
`Content-Type: text/plain` header.


This is the minimal required setup needed in order to setup an 
application using onion framework and from here you can conquer the 
world! Check other sections from the documentation to get more in-depth 
understanding/knowledge of the routing capabilities, the concept of 
modules and everything else.
