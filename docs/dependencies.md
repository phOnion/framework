The app comes with a flexible DI container that is fully compatible with
[PSR-11 container specification](https://www.php-fig.org/psr/psr-11/),
that provides most of the functionality you'd need in order to build
your project.

You can manually add definitions using the
`Onion\Framework\Dependency\Container` or entirely rely on the
reflection-based implementation
`Onion\Framework\Dependency\ReflectionContainer` but it will not be able
to resolve dependencies that are injected via setters or scalar type
dependencies.

Also there is the `Onion\Framework\Dependency\ProxyContainer` which
allows to attach multiple containers and delegate dependency resolution
process between multiple containers (a good use-case could be to use it
with the `Onion\Framework\Common\Configuration\Container`) to allow
retrieval of configurations by using the same container that is used to
fetch your dependencies, this approach adds transparency as to where are
your configurations stored - be it local files, environment variables or
some external provider. Also keep in mind that the registered
dependencies will be resolved whenever requested from the container (
lazy instantiation).

## Single-instance dependencies

The `singleton` method on the `Container` class indicates that the
defined dependency should be instantiated only once, which is helpful
for database connections, message queue connections, HTTP client
configured to hit only a specific domain or anything else that does a
connection or is necessary to have a shared object instance returned
whenever it is called (the default `RouterServiceProvider` uses it for
the `Collector` class) when this is beneficial (
[See wikipedia's entry on singleton pattern](https://en.wikipedia.org/wiki/Singleton_pattern)
for more information).

The `singleton` allows to define implementation of the service by:

- passing an instance of a
  `Onion\Framework\Dependency\Interfaces\FactoryInterface`or a closure
  that matches the interface's `build` signature
- a FQCN that will can be resolved using the `ReflectionContainer`; or
- manually instantiate the object when defining the dependency.

The recommended way is to use a factory, because it provides more
control as to how the instance is created.

## Factory-created dependencies

What we said about the singleton definition above is also valid for
the factories as well, with a couple of differences:

1.  The instantiation of the class happens every time the dependency is
    requested
2.  You can't manually instantiate the instance behind the binding.

And obviously the method name is not `singleton`, but `bind` 🙂.

## Tagging dependencies

Tagging is useful if you are to retrieve a group of dependencies.
This is utilized internally inside the `HttpServiceProvider` when
building the `RequestHandlerInterface` to retrieve all of the
application middleware.

You can add tags in a couple of ways, first is at the time of definition
as the 3rd argument for both `bind` and `singleton` is a list of tags to
to it, and there is also the dedicated method to do so for an already
defined one via the `tag` method, which has the following signature:
`$container->tag($dependencyId, 'tag1', 'tag2', /* ... */, 'tagN');`

Retrieval of dependencies based on tag is done through the `tagged`
method and always returns a list of items, be it an empty one, so to
retrieve all the `middleware` tagged dependencies, you would do
`$container->tagged('middleware');` (as done inside the
`HttpServiceProvider`).

Which really means that the following would produce the same result.

```php

// ...

$container->singleton(Dependency::class, fn () => /* init logic */, ['tag1']);
// or $container->bind(/* ... */); see previous sections for differences

// AND

$container->tag(Dependency::class, 'tag1');

```

## Extending dependencies

Extending dependencies is also possible using the `extend` method. The
way this works is - you provide a function that has the following
signature `fn (object $dependency, ContainerInterface $container, string $id): object => /* your logic */;`. With this you can do setter injection or
completely replace the instance that will be returned (although isn't a
recommended way of doing it, see "Overwriting dependencies" section for
more information how to do it using the recommended way).

For example you can have your `LoggerAwareInterface` implemented by
a hypothetical service, you need to inject the service if it is available.
Obviously you can do a check as part of the constructor, but it is not a
concrete dependency and your class can work without it, you can simply
do something like the following so that you will have a clearer service
instantiation logic by doing something similar to the following:

```php

// ..

class HypotheticalServiceProvider implements ServiceProviderInterface
{
  public function register($container)
  {
    $container->bind(
      HypotheticalService::class,
      static fn ($container) => new Service(/* hard dependencies */)
    );

    $container->extend(
      HypotheticalService::class,
      static function ($service, $container) {
        if ($container->has(LoggerInterface::class)) {
          $service->setLogger($container->get(LoggerInterface::class));
        }

        return $service;
      }
    );
  }
}

```

And while certainly this can be done in the factory it makes it a little
bit more clearer as to what is mandatory and what is an optional
dependency.

## Aliases

The possibility to add aliases for dependencies can be relatively useful
for certain cases where you want to use a more context specific key for
a dependency or identifying a strategy (for example for caching driver).

Say for example you are doing a CMS like wordpress and since your users
can decide what caching (or database for that matter) driver to use, you
need to support multiple of them. If you define a `cache:redis` and
`cache:apc` how do you retrieve the appropriate one? You can always check
inside the factory in which you inject the dependency, but this will
result in a lot of duplication and is prone to human errors. You can go
the route of not supporting multiple ones, but only one, say `cache:redis`,
but then some of your customers must have a redis server available in
production which might not always be the case. So.. how? Well these are
the cases where aliases can come in handy, because we can add a dynamic
alias inside the hypothetical `CacheServiceProvider`'s `boot` method,
like so:

```php

// ...
class HypotheticalCacheServiceProvider implements ServiceProviderInterface
{
  public function register($container)
  {
    $container->singleton('cache:redis', fn () => /* ... */);
    $container->singleton('cache:apcu', fn () => /* ... */);
    $container->singleton('cache:void', fn () => /* ... */);
  }

  public function boot($container)
  {
    if (extension_loaded('redis')) {
      $container->alias('cache', 'cache:redis');
    } else if (extension_loaded('apcu')) {
      $container->alias('cache', 'cache:apcu');
    } else {
      $container->alias('cache', 'cache:void');
    }
  }
}
```

And later when you need to inject the dependencies you can retrieve the
`cache` key which will allow your application to handle cases
auto-magically behind the scenes, without having to care what the
supported cache backends are available on the actual environment where
your application is deployed. The same can be done with multiple
database drivers or, well, any case where multiple strategies can be used
and can be predetermined and will not require pollute your code with
complex checks or depend on manual enablement flags, that can cause
issues, which can happen if the flag for redis is enabled, but redis
extension is not available on the server - I smell deadlocked
application.

Also apart from such scenarios this can be used to further enable the
design by contracts ([wikipedia article on DbC](https://en.wikipedia.org/wiki/Design_by_contract)) by setting having your factories define the interfaces
they need injected and you aliasing the concrete implementation via
aliases as we do in the "Hello, World" example with the `EmitterInterface`
since `PhpEmitter` is defined inside the default `HttpServiceProvider`
we set the alias to enable the auto-wiring to know what to inject when
it meets a dependency type-hinted with `EmitterInterface`.

## Overwriting dependencies

In order to overwrite already registered dependencies, like those coming
from an external package, you can do the `unbind`-`bind` combo. The
differences with `extend` are the following:

1.  `unbind` demonstrates the intent to replace the given dependency.
2.  Will fail if done outside of a service provider, which is to serve
    as guard against hot swapping dependencies that have already been
    initialized and injected in other places.
3.  Throw an error if you attempt to remove a non-existing dependency
4.  Will clear the `singleton` instance (if any) so the new instance is
    returned wherever requested.

All of these are important either for making your code more
understandable for others and especially the last one, to ensure a
consistent behavior of the container where if you overwrite an
instantiated `singleton` dependency your overwrite will not be executed
since there is already an existing instance.

So, in order to handle all the cases if you don't know for certain
whether or not a dependency exists you can register it inside the `boot`
method of a custom service provider, like so:

```php

class HypotheticalServiceProvider implements ServiceProviderInterface
{
  public function register($container)
  {
    /* register new dependencies */
  }

  public function boot($container)
  {
    try {
      $container->unbind(ReplaceableDependency::class);
    } catch (\LogicException $ex) {
      // Log that the service provider is somehow executed
      // outside of service provider cycle, in which case
      // you should again read this section carefully :)
      // But seriously tho, this case should never occur
      // if it does - you are doing it wrong
    } catch (\InvalidArgumentException) {
      // Nothing to do, since it will appear that the dependency
      // does not exist, so nothing to do really
    }

    $container->bind(
      ReplaceableDependency::class,
      fn () => new ReplacementInstance()
    );
  }
}

```

## Service providers

The part that we've mentioned previously, but didn't really explained is
the ServiceProvider. Basically it is a class used to facilitate the
process of registering the dependencies. Apart from being able to use
the `unbind` method only from inside it and having a method in which you
add dependency bindings, it also allows you to hook in the dependency
loading via the `boot` method, which isn't part of the base interface,
but part of the `BootableServiceProviderInterface` since it is an
optional hook.

The order of execution of the service provider happen in the same order
as they are provided to the container and this goes for the calls to
both `register` and `boot` methods.
