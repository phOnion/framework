# Introduction

In this section we'll get through a simple hello world app to help you
get a better idea as well as to understand how this thing actually works.

First thing is the index file, it is the entry point for the application.
The application consists of middleware, which if you are not familiar
with you should think of as (actual) onion's layers (now you get where the name
comes from). See:

- [PSR-15 Specification](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-15-request-handlers.md)

- [Why Care About PHP Middleware?](https://philsturgeon.uk/php/2016/05/31/why-care-about-php-middleware/) by _Phil Phil Sturgeon_

## Installation

So with that being said, lets jump right in with the installation
and setup of the "hello world" project:

1. `composer require onion/framework:@dev`
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

   /** Initialize the dependency container */
    $container = new Framework\Dependency\Container();
    /** Register the predefined provider to obtain access to common dependencies */
    $container->register(new Framework\Http\HttpServiceProvider());

   /** Create alias to the concrete implementation of the emitter */
    $container->alias(Framework\Http\Emitter\Interfaces\EmitterInterface::class, Framework\Http\Emitter\PhpEmitter::class);

   /** Group the global middleware classes */
    $container->tag(Framework\Http\Middleware\ResponseEmitterMiddleware::class, 'middleware');
    $container->tag(Framework\Http\Middleware\HttpErrorMiddleware::class, 'middleware');

   /** Trigger the request handler to start processing */
    $container->get(\Psr\Http\Server\RequestHandlerInterface::class)
        ->handle(\GuzzleHttp\Psr7\ServerRequest::fromGlobals());
```

Now, if you've opened up the browser you probably have noticed that this ins't actually a "Hello, World" because, well, there is no "Hello, World" in the browser, but we will fix that in a second. But first lets see what is happening under the hood:

1.  We require composer so that our dependencies are autoloaded
2.  We instantiate the main container that will be the central point
    from which we will resolve our dependencies.
3.  We register the service provider, that provides some basic configuration
    out of the box that we can use (they also can be overwritten so don't worry)
4.  We create an alias via which is the dependency of the `ResponseEmitterMiddleware`.
    This is done like this, so it can be easily switched with another implementation
    if a need arises without having to do any major changes.
5.  We tag the `middleware` dependencies so that they can be auto-injected
    in the request handler
6.  We pass the request through the request pipeline where the `ResponseEmitterMiddleware`
    sends it to the browser.

Now lets turn this into an actual "Hello, World!" by actually outputting something.
Add the following after the second call to `$container->tag(...)`:

```php

// ...

$container->singleton('hello-world-middleware', fn () => new class extends Framework\Controller\RestController
{
    public function get(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Server\RequestHandlerInterface $handler
    ): \Psr\Http\Message\ResponseInterface {

        return $handler->handle($request)->withBody(\GuzzleHttp\Psr7\Utils::streamFor('Hello, World!'));
    }
}, ['middleware']);

// ...

```

If you refresh your browser you should see the message that makes our
world turn - `Hello, World!` 🎉. Now... this looks a kinda ugly, with
the namespaces not being imported and the underutilized (in my opinion
at least) anonymous class, but for the purposes of a "Hello, World" it
serves its purpose just fine. So after we've cleaned that up we can
continue with the explanation of what are we doing in this example:

1.  We add a new dependency to the container identified by
    `'hello-world-middleware'`
2.  We define a function that returns a "controller" class, which has
    just one `get` method that handles the `GET` request the browser is
    doing
3.  We call the handler in order to not break the middleware execution
    chain (more on that later 🙂);
4.  And then we set the body of the response to `'Hello, World!'` and
    return it.
5.  After we've defined what should happen in our `get` method, we add a
    tag to the dependency - `middleware` - so that it is returned as part
    of the stack we saw earlier

Since we've introduced another new class, the `RestController`, I think
it is worth explaining what it does. Basically it is just a syntax sugar
on top of the middleware, that allows for clearer separation when a
single class might handle multiple HTTP methods, without having a single
`process` method with `if` or `switch` for every controller that exists
inside our application. Also it also handles `HEAD` requests (i.e
removing the body from the `get` response if such exists); and also
include `Allow` header in response to `OPTIONS` calls.

And that is it, now after we've got the most important programming
exercise out of the way it is time to continue on with the routing. (Oh, and the middleware classes are handled as part of the "Middleware" section of this documentation 😉)
