## Introduction

Modules are not a new concept in the development world, nor they re new
to the PHP frameworks/applications. The implementation used here is not
too different from what has already been implemented in other frameworks
but the intent is clear and is aimed at enforcing the separation of
applications in logical parts, rather than having module provide
helper functionality (although they can also be used in that way, but
might fee less intuitive)

## Setup

A module should have it's logic as self-contained as possible, except
for configurations, since that will change from application to
application.

The only requirement in order to ensure consistency (as of now it is not
enforced) is for the module to serve as a factory that MUST return
instance of `Application`, since application is a middleware it does not
introduce a very specific behaviour, except maybe for having a separate
middleware delegate defined and a separate router for the module itself.

Internally the `ModuleDelegateFactory` is building a router with every
instance `Application` assigned it's own prefix inside a `module` key.

## Configuration

The minimal configuration to the container is:

```
return [
    // .. other configuration
    'modules' => [
        MyApp\API\Module::class
    ]
]
```

An example module class:

```
namespace MyApp\API;

use Interop\Container\ContainerInterface;
use Onion\Framework\Application\Application;
use Onion\Framework\Application\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    private function getConfigurations(): array
    {
        return [
            'routes' => [
                [
                    'pattern' => '/api/users',
                    'middleware' => [
                        UsersApiController::class
                    ]
                ]
            ]
        ];
    }

    public function build(ContainerInterface $container): Application
    {
        return new Application
            // Retrieve everything from a contextual container (optionally related to the master container)
            $this->getOwnContainer(\Interop\Middleware\DelegateInterface),
            $container->get(EmitterInterface::class)
        );
    }
}
```

It is recommended for a module class to fetch the `EmitterInterface`
from the main container, instead of defining it's own since it will
avoid issues with custom emitter implementations if applicable. Also
note that the route must have the prefix defined by default in order to
avoid this duplication (if that is your thing, but do keep in mind that
the intent behind this kind of modules is that a module can and should
be able to function as it's own application and not only as a module of
another). Once the initial testing has been performed a CLI application
will be developed allowing for the generation of module classes from
existing applications, which will empower use-cases such as minimal blog
to be embedded inside another application at a prefix; as well as
install/uninstall steps, but that is TBD as of now so don't rely
completely on that, but do keep in mind that the reasoning behind the
modules is here to stay.
