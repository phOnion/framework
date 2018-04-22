# Introduction

Modules are a way to separate the application in order to preserve separation
of code components and avoid your application to grow in to a monolith that
is going to kill a kitten, destroy the world, make you insane and/or send
you to hell. So to allow this, the modules are designed to be separated
based on a route prefix. In order to achieve that.

In order to achieve that, You will probably need a localized routes defs
that will be loaded throughout the module and utilize the DelegateContainer
to resolve the remaining dependencies.

## Setup

In this example we would assume you want to have a module that is handling
the `/integrations` prefix in your app. So first we will go through adding
the definition for a route to the global application:

```php
return [
    [
        'name' => 'integrations',
        'pattern' => '/integrations',
        'request_handler' => 'IntegrationsModule',
        'class' => \Onion\Framework\Router\PrefixRoute::class,
        'headers' => [
            'Authorization' => true,
        ]
    ]
];
```

*Note we've omitted the `middleware` key.*

This will ensure that whenever we receive a request that starts with
`/integrations` it will be sent to that request handler while also requiring
the client to provide an `authorization` header for access, and register it
in the container as `IntegrationsModule`. From there for the executed routes
the request will come as if there was never a prefix as it will get stripped
to allow the applications to not have coupling to the routing they have.
For example, every route you define in the _module_ must not contain the
prefix or the routing will match only `/integrations/integrations/...`
and we don't want to do that, right?

With that being said a factory for our imaginary module would look more
or less like this:

```php
<?php
namespace App\Module\Integration;

// imports...

class IntegrationModuleFactory implements FactoryInterface
{
    private getLocalContainer(): ContainerInterface
    {
        return new Container(compileConfigFiles(__DIR__ . '/../../config'));
    }

    public function build($delegateContainer)
    {
        // Note here we need to retrieve the main application
        return $this->getLocalContainer()->get(Application::class);
    }
}
```

And from here on the routing takes care of it and when the route matches
in the parent application it will hand over the execution to the module
and we are done. The middleware will stack between the application and
module. Just when you are intentionally building a module, you might want
to exclude an error handler in the modules in order to allow the application
itself to handle the error handling, although that will depend entirely on
your design and components the possibilities are more or less limitless.
