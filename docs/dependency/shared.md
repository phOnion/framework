
There is also the root `'shared'` meta-key that indicates to the container
which resolutions should be cached and return the same instance on every
consequent request made to the container. A good example for this would be
the `\PDO` or other object that upon instantiation opens a connection to
another remote service. It is supported for both `invokable` and `factories`
entries. In those cases you might do something like:

```php
return  [
    'database' =>  [
        'driver' => 'mysqli',
        'database' => __DIR__ . '/some.db',
    ],
    'factories' => [
        \PDO::class => \My\Pdo\Factory::class,
    ],
    'shared' => [
        \PDO::class,
    ],
];
```
What this will do is upon the first request the PDO object will be created
and if it resolves without any problems the same instance will be used
whenever you request `\PDO::class` avoiding opening of multiple connections
for every class that is resolved through auto-wiring or requiering always to
keep track of a global connection pool or any other complex technique that
will almost certainly be out of the scope of what you are trying to do (
Except if you are building an ORM, which you shouldn't unless you have a
really good reason to do so, but srsly... please dont <3 ).
