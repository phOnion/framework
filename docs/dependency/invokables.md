
`invokables` are mappings from type to implementation, this is mainly intended
 for cases where a class binds to an interface and when the container needs to inject
 a blank instance of a given class. This will not perform any operations on the instance
 so it is expected those to be instances without a constructor or with a constructor that
 does not need any arguments. Consider it as a mapping between `interface => implementation`
  
A good practise is to use `\Acme\App\SomeClass::class` instead of `'someclass'` as keys so
it allows for auto-wiring and makes the code more obvious, more readable and decoupled from 
the configuration.

For example:
```php
//...
    'invokables' => [
         \Psr\Http\Message\ResponseInterface::class => Zend\Diactoros\Response::class,
         \Zend\Diactoros\Response\EmitterInterface::class => \Zend\Diactoros\Response\SapiEmitter::class,
    ]
//...
```

Creates a mapping that whenever an object requires `ResponseInterface` the container will return 
`new Response()` and the same goes for the `EmitterInterface`, this is useful when you have only
type-hint against an interface instead of a specific implementation, this will trigger the container
to perform a lookup on the dependencies of the defined class in an attempt to return a fully setup
instance. And while invokables are available it is not necessary to define each and every class in this mapping,
but rather only those which bind to interface, for those that are type-hinted to a concrete 
implementation the container will attempt to do it's best to resolve it automatically from the available 
types and other definitions passed. 

But in scenarios where you need a specific configuration that the container cannot resolve
you can turn to the next keyword
