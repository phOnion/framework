
`invokables` are mappings from type to implementation, this is mainly intended
 for cases where a class binds to an interface and when the container needs to inject
 a blank instance of a given class. This will not perform any operations on the instance
 so it is expected those to be instances without a constructor or with a constructor that
 does not need any arguments. Consider it as a mapping between `interface => implementation`

A good practice is to use `\Acme\App\SomeClass::class` instead of `'someclass'` as keys so
it allows for auto-wiring and makes the code more obvious, more readable and lowers the
chance of making typos as most editors will suggest the class proper class name.

For example:

```php
return  [
    'invokables' =>  [
         \Psr\Http\Message\ResponseInterface::class =>
                \Guzzle\Psr7\Response::class
    ]
];
```

This will create a mapping that will return a new instance of `\Guzzle\Psr7\Response` whenever
`$container->get(\Psr\Http\Message\ResponseInterface::class);` is used, also will allow automatic
injection to classes that do not have factories but have type-hints in their constructor, which
can tremendously reduce the amount of definitions that need to be defined manually if classes
are defined with that in mind. A big use case for this could be when you define only the interface
implementations and instead of defining a factory for each class, you can type-hint against the
interface and do a `$container->get(MyAwesomeClass::class)` and boom all dependencies will be
injected automatically without sacrificing anything, not changing anything in the code.

Also, if the type-hint does not point to a object, but is a scalar the container will try to look
for definitions with the same name as the name of the variable, allowing a constructor with paramteres
`$fooBar` to be resolved to the value of the `foo.bar` using dot-notation for levels.

*Be careful tho as to not get in to a situation in which the wrong variables are injected. This
is intended for very small and/or simple applications, for those that might grow it is advisable
to stick to explicitly defined factories as to not fight mysterious bugs that don't make a lot of sense.*
