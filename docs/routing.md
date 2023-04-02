# Introduction

So, when the application receives an HTTP request, it can call a specific
action to handle it generate the appropriate response. You can restrict
the the availability of a route to specific set of methods.

So lets start by registering the default provider, add an alias to
the dependency container so the internal resolution can resolve it and
register the middleware that dispatches the matched route:

```php
// ...
$container->register(new Framework\Router\RouterServiceProvider());

// ...
$container->alias(Framework\Router\Interfaces\RouterInterface::class, Framework\Router\Router::class);

// ...
$container->tag(Framework\Http\Middleware\RouteDispatchingMiddleware::class, 'middleware');
```

So first we register the provider so we don't have to define them by hand
(you can replace them afterwards), then we set an alias to identify the
actual router implementation and finally we register the middleware that
will invoke the associated action.

But now if you try to refresh your browser, you should get a 404 response,
this is expected because the router doesn't know about any resources. In
order to change that, we will register a couple of routes in order to
see how routes are defined:

```php
// ...
$collector = $container->get(Framework\Router\Interfaces\CollectorInterface::class);

$collector->add(['GET'], '/', fn ($request, $handler) => $handler->handle($request->withBody(
    \GuzzleHttp\Psr7\Utils::streamFor('Hello, World!'),
)));

$collector->add(['GET'], '/{name}', fn ($request, $handler) => $handler->handle($request->withBody(
    \GuzzleHttp\Psr7\Utils::streamFor(
        "Hello, {$request->getAttribute('route')->getParameter('name', 'hooman')}!"
    ),
)));
// ...
```

The first one is self-explanatory - registers the `/` route to return
"Hello, World". But the second one is a little bit different - it
contains a variable that you can retrieve inside your code, either by
getting the `route` or `Onion\Framework\Router\Interfaces\RouteInterface`
and from there you can retrieve the route parameters, as you can see in
this example when we retrieve the name provided. To try it out, navigate
to `http://localhost:12345/user` or anything else instead of `user` to
see how the message changes.

---

## Parameters

As you saw from the example above, the default router supports route
parameters using `{param}` to define them.

### Constraints

The parameters can also have constraints defined at in them, such as
allowing only numbers, being part of an enumeration, etc. in order to
do that you have to do `{param:[0-9]+}` (or as shorthand `{param:\d+}`)
In the case of enumeration {param:option1|option2}.

If you are familiar with the concept of routing feel free to skip to the
next section where you can find how exactly the routing can be setup.

So, routing is an very important part of a web application, it is
responsible invoking different logic based on the path that was used by
the user to reach your application, for `http://example.com/foo` that
would be `/foo`. There are various different ways applications can handle
routes, some auto-resolve to a set of controller and action based on the
input others

### Conditionals

Every parameter can be conditional/optional, you just need to add `?`
after the closing curly brace of the parameter definition. The default
parser has support for conditional parameters in the middle of a route
you just would need to be careful to not accidentally overlap with another
route because the precedence would be the first matched route.

```php
$collector->add(['GET'], '/foo/{id:\d{4}}?/{baz:\w+}', /* ... */);
```

Would match with both `/foo/1234/test` and `/foo/test` and dispatch the
same action
