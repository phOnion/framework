The app comes with a very capable DI container, that provides the essential
functionality to get an app running with as little issues as possible. It is
pretty similar to the one provided from ZF, but with some features omitted. 
The container's dependencies are passed as an array in it's constructor and 
have 3 special keys: `invokables`, `factories` and `shared`

Everything outside of these 3 keys is treated just as a value and can be retrieved by calling 
`Container::get('someKey)` and that will return the value of 'someKey' as-is, without performing
any operations on it. Also if you need to access a specific key which is located in a nested 
array you can use a dot notation, which the container will interpret as indication for a level
of the array. Say for example, you have an array:

```php
[
    // ...
    'doctrine' => [
        'connection' => [
            'dbname' => 'foo',
            // ...
        ]
    ]
    // ...
]
```

In order to access `'dbname`, you might have to do at least 2 ifs (to ensure the key is there)
and definitely will have to make something like: `Container::get('doctrine')['connection']['dbname']`,
not the prettiest... This is where this could come in to play, instead of doing that you can just du
`Container::get('doctrine.connection.dbname'); // Returns 'foo'` and you are all set, and it looks 
clearer too + on thje bonus side if you are writing a distributable module, you can be sure that the
container will throw the appropriate error if any key is not defined and notify the user, no more 
piles of `if`s in your factories :)
