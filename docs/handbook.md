 - [What is a component?](#component)
 - [Define a route?](#route)
 - [Define a dependency?](#dependency)
 - [Register a global middleware?](#middleware)
 
 -----
 
<div id="component"></div>
**What is a component?**

A package that contains it's own configuration and is autoloaded
------------------------------------------------------------------------

<div id="route"></div>
**Define a route?**
    
A route has the following configuration keys available:
    
- `name` (optional): *Allows later retrieval of the route by name from the router.*
- `pattern` (required): *The path which this route handles when a request is received.*
- `middleware` (required): *A list/array of middleware that represent the request handling.
 See [Define a global middleware?](#middleware) for information how to pupulate the middleware array.*
- `allowed_methods` (optional): *A list/array of HTTP methods that the route is capable of handling*
      
The whole structure of an `routes` configuration is as follows:
```
[
    'routes' => [
        [
          'name' => 'home',
          'pattern' => '/',
          'middleware' => [],
          'allowed_methods' => ['GET']
        ], // ... other routes
    ]
]
```
  
-----

<div id="dependency"></div>
**Define a dependency**

There are a 2 types of dependencies, each of which can be `shared`:

  - `invokables` - Create an object without any special requirements
  - `factories`  - Objects that are create by factories, every factory 
  must implement `Onion\Framework\Interfaces\ObjectFactoryInterface` or
  an exception will be thrown from the container.

Shared objects use their own identifiers and link to the identifiers of 
any kind of definition and indicate the the same instance of the object
they are referring must be returned by every request to the container.

A sample `dependencies` definition:
```
[
    'invokables' => [
        \App\Demo\Controllers\Index::class => \App\Demo\Controllers\Index::class // Will init & return instance of the class
    ], 
    'factories' => [
        \App\Demo\Controllers\ComplexController::class => \App\Demo\Controllers\Factory\ComplexControllerFactory::class // Will return the result of the factory object.
    ]
]
```

-----

<div id="middleware"></div>
**Define a global middleware**

You have to define the middleware as a dependency 
(See [Define a dependnecy](#dependency) for information how to do it)
Then just add it's identifier in the a `middleware` section in a 
config file and it will be pulled from the container.
