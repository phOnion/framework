## Basic factories
`factories` are another essential part of the container. These are manually created classes that
implement `\Onion\Framework\Dependency\Interfaces\FactoryInterface` and serve as "formulas" how
to build the dependency.

For example:

```php
return  [
    'factories' =>  [
         Framework\Application\Application::class =>
                Framework\Application\Factory\ApplicationFactory::class,
    ]
];
```

This tells the container, whenever `RouterInterface` is requested, to invoke the `RouterFactory`
which returns an instance of the interface. This is the flexibility point of the container (not an
exclusive feature but still) since it allows you to define manually the way an object instance is
build and also provides an abstraction between the container's resolution and implementation details.
If you use setter injection you will have to use factories, as the container does not provide any
way to auto-wire them. Also setter injected dependencies, could be considered optional and may not
be needed for the class, no need to spend time on them.

---

## Abstract factories

Abstract factories are aimed to allow the creation of a factory for more
than can determine based on the key what object to return. In general it
is a factory factory as it must return a `FactoryInterface` which will
return the real object to be injected. For example if an abstract factory
is used to build a cache service an "alias" key can be used like:

```php
// ...
    'factories' => [
        'cache:redis' => AbstractCacheFactory::class,
    ],
// ...
```

There the abstract factory can switch from which configuration to use
without the need of defining factory for each option.

This could come in handy when dealing with multiple database, cache or
other such backends or multiple servers, where the difference can be
very small (even a single string, like the `driver` key in db configs).

Abstract factory is defined under the `factories` key, with the only
just like with any other factory and all is handled transparently for
the application.

---

## Memory caching

And if an object is often required, but does not need to be instantiated multiple times,
there is the `shared` key. It allows you to define the a list of factory keys that should not
be triggered on every request to the container, but actually "share" the same instance
of the first result. This can be useful in scenarios like, constructing database connection,
instantiating connection to a remote service, etc. The main intent for this is to be used
for heavy objects which might be required on multiple places but sharing the same instance
makes sense.
