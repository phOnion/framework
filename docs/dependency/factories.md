- `factories` are another essential part of the container. These are manually created classes that
implement `\Onion\Framework\Dependency\Interfaces\FactoryInterface` and serve as "formulas" how
to build the dependency.

For example:

```php
// ...
    'factories' => [
         Framework\Application\Application::class =>
                Framework\Application\Factory\ApplicationFactory::class,
    ]
//...
```

This tells the container, whenever `RouterInterface` is requested, to invoke the `RouterFactory`
which returns an instance of the interface. This is the flexibility point of the container (not an
exclusive feature but still) since it allows you to define manually the way an object instance is
build and also provides an abstraction between the container's resolution and implementation details.
If you use setter injection you will have to use factories, as the container does not provide any
way to auto-wire them. Also setter injected dependencies, could be considered optional and may not
be needed for the class, no need to spend time on them.

---

And if an object is often required, but does not need to be instantiated multiple times,
there is the `shared` key. It allows you to define the a list of factories that should not
be triggered on every request, but actually "share" the same instance of the first result.
This can be useful in scenarios like, constructing database connection, instantiating
connection to a remote service, etc. The main intent for this is to be used for heavy
objects which might be required on multiple places.
