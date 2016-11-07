## Introduction

THe built in router uses the strategy pattern in order to achieve 
maximum flexibility in terms of pattern syntax & capabilities. 

In order to provide routes to the router using the build-in factory
the key `routes` must be defined inside the DI container and look like 
the following:

```
return [
    // .. other container definitions
    'routes' => [
        [
            'pattern' => '/',
            'middleware' => [
                /**
                 * Implementations of ServerMiddlewareInterface specific
                 * for the current route
                 */
            ],
            'methods' => ['GET', 'HEAD'], // Optional, supported methods
            'name' => '' // Optional, used with `getRouteByName`
        ], [
            // .. another route definition
        ]
    ]
];
```

Keep in mind that `pattern` and `middleware` MUST be present, otherwise 
exception will be thrown.

## Parsers

A route parser is responsible to translate the route definition key 
`pattern` to a matcher processable representation, at the moment the 
only real usage of it is in the `Regex` parser and matcher pair, where
The parser allows the usage of route patterns such as 
`/articles[/[id:*]]`, which allow the matcher to extract the parameter
`id` and also match even if it is omitted, resulting in the route 
defined being responsible for handling `/articles` as well as 
`/articles/5` and `/articles/why-chose-onion-framework`


## Matchers

A matcher is responsible for solely checking if the current request URI
matches the route pattern provided (as the name suggests) and in the 
case of the `Regex` pair extract the parameters form the URI and return 
them. 

## Available pairs

The available pairs are listed below in the 'Parser' => 'Matcher' syntax

 - `Flat` => `Strict`
 - `Regex` => `Regex`
 

There is also a single matcher, that does not correspond to any parser 
and can be used with both parsers (although might be a better fit for 
the `Flat` rather than `Regex`, but that depends on the implementation).
Internally it is used only inside the `ModuleDelegateFactory`, which is
a special case discussed in the `Modules` section, but if it is fitting 
your use case feel free to use it, but beware that it uses `substr` to 
determine if a prefix is present so using it with a route with pattern
`/` will result in all routes being passed to that controller, which is 
raraly the intended behaviour.

