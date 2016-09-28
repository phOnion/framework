## Dependencies

Now as we have the core files set-up and ready to go, let's start 
defining the gears that will actually make our application to do some
stuff. Now we will provide the definitions for the core stuff.
 
create a file named `dependnecies.global.php` in `config/autoload/` and
paste the following inside it:

```
<?php
/**
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 */

use Onion\Framework;
use App\Demo;

return [
    /*
     * A map that describes the dependencies being used by the application
     */
    'dependencies' => [
        /**
         * This section defines classes which do not require any special
         * initialization procedure.
         */
        'invokables' => [
            /**
             * This handles the sending of the ResponseInterface object
             * to the client. Sets headers, body, cookies, etc.
             */
            Zend\Diactoros\Response\EmitterInterface::class =>
                Zend\Diactoros\Response\SapiEmitter::class,
            /**
             * Defines the implementation of ResponseInterface to use
             */
            Psr\Http\Message\ResponseInterface::class =>
                \Zend\Diactoros\Response::class,
            /*
             * Value object that holds all information about the
             * defined route
             */
            Framework\Interfaces\Router\RouteInterface::class =>
                Framework\Router\Route::class,

            /**
             * The object which is capable of invoking middleware
             * that is registered with it (see PSR-15)
             */
            Framework\Interfaces\Middleware\StackInterface::class =>
                Framework\Http\Middleware\Pipe::class,
            /**
             * Implementation of a parser that is responsible
             * for translating the route 'pattern' key to usable
             * representation that can be matched against the
             * 'path' of the current request
             */
            Framework\Interfaces\Router\ParserInterface::class =>
                Framework\Router\Parsers\Regex::class,
        ],
        /**
         * Mapping the class name to the factory which holds the "formula"
         * for instantiating and configuring a given object.
         */
        'factories' => [
            // Pretty much catch-all, return new instance of the object every
            // time (calls the factory and returns it's output)

            /**
             * Defines the implementation of the ServerRequestInterface to use
             */
            Psr\Http\Message\ServerRequestInterface::class =>
                Framework\Factory\ServerRequestFactory::class,
            /**
             * Factory that instantiates the built-in router or
             * any other compatible implementation also the default
             * implementation is a middleware and is capable of
             * dispatching the request on it's own
             */
            Framework\Interfaces\Router\RouterInterface::class =>
                Framework\Router\Factory\RouterFactory::class,
            /**
             * Factory that configures the StackInterface instance with
             * the global application-level middleware.
             */
            Framework\Http\Middleware\Pipe::class =>
                Framework\Factory\GlobalMiddlewareFactory::class,
            /**
             * Instantiates the root Application object that
             * orchestrates the execution.
             */
            Framework\Application::class =>
                Framework\Factory\ApplicationFactory::class,
        ],
        /*
         * Share the dependency instance across the application.
         * This is useful when working with database connection
         * objects, connections to cache and/or other expensive
         * operations.
         */
        'shared' => [
            /**
             * Share the instance of the router across the application
             * in order to allow the usage of view helpers to generate
             * routes by name
             */
            Framework\Interfaces\Router\RouterInterface::class =>
                Framework\Interfaces\Router\RouterInterface::class
        ]
    ]
];
```


as you can see there is a description about every part of that file 
(pretty proud of myself about it:D ). Anyway as you can see we are using
interface name instead of some string identifier. This is pretty 
important part as you can ensure on the container level that the 
dependency will be instance of that interface + shows what the contract 
between objects. when you look at
`\Psr\Http\Message\ResponseInterface::class => \Zend\Diactoros\Response::class`
you instantly know that the response object implements that interface
and hence is interchangeable with other implementations of the 
interface.

But just to make sure it is perfectly understandable there are (at the
moment at least) 2 types of dependencies: 

 - `invokables` - These are 1-to-1 mapping and return new instance of 
 the object being defined as value on every call to `ContainerInterface::get()`
 - `factories` - These are, well, factories their values must refer to
 classes which implement `Onion\Framework\Interfaces\ObjectFactoryInterface`
 in order for the container to be able to get the dependency and return 
 it. Every factory, by default, gets called whenever the identifier is
 requested by the container.
 
But as you can see from the above configuration snippet, there is 
another `shared` entry. That is not a direct type of dependency, but
rather a meta section in which the container will look before invoking
any dependency. Its purpose is to simple allow the container to return 
the same value of an object instead of initializing it every time.

This is particularly useful in the scenario when, for example you
ask the container to return an instance of the `EntityManager` in
doctrine and don't want to create a new object every time, but rather
return the same object every time, or when you use a cache layer. In 
either way you don't want to bombard the server with connections on
every single request, but reuse a single one (preserve app memory 
footprint, no additional delay in when connecting, can reuse state of 
the object)

----

## Middleware

Now create another file in the same directory `config/autoload` named
`middleware.global.php` and paste this in to it:

```
<?php
/**
 * PHP Version 5.6.0
 *
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 */

use Onion\Framework;

return [
    'middleware' => [
        /*
         * !!! IMPORTANT !!!
         * The route dispatcher must be the last middleware.
         */
        Framework\Interfaces\Router\RouterInterface::class
    ]
];
```

This defines that the default router implementation will be called 
(since it is also a an instance of 
`Onion\Framework\Interfaces\Http\Middleware\ServerMiddlewareInterface`,
therefor it can be used that way (note this is not a prat of the 
specification of the interface). If you now try and run the application
