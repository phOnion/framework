## About

This is a minimalistic core framework that complies with, supports and uses various 
PSRs:

 - PSR-2 (Coding Standard)
 - PSR-4 (Autoloading)
 - PSR-7 (HTTP Message)
 - PSR-11 (Container Interface)
 - PSR-15 (HTTP Middleware)
 - PSR-16 (Simple Caching) - *container wrapper only*
 
While it can be used to power a full-blown application framework as of now
it is mainly oriented towards the core tools needed for building an API.
Regardless of the fact that it does not come with any transformers/serializers/etc.
for doing so there is a complimentary package `onion/rest` which provides the ability
to build APIs that return multiple RESTful responses. It is built only with depending
on `zendframework/zend-diactoros` (PSR-7 implementation) so there are no wrappers
around any external frameworks/components/bundles/etc. so the developer(s) have absolute
control over what they want to use.

The components included are: DI Container, Middleware Dispatcher, Router and Hydrator traits.
nothing more, nothing less. There are complementary packages, that are aimed to add
some additional features which are more nice-to-have, but not necessarily needed or always 
needed by everyone, so they will be implemented as separate optional packages and some could
be included in the if they prove to be useful for a majority of users and stable enough.

*NOTICE*
There is no and there will not be an ORM, Template Engine or any-high-level-feature. There are
pleanty of those already available, there is no need to reinvent the wheel.

  - ORM:
    - Doctrine (1 and 2)
    - Eloquent
    - Propel
  - Template Engine:
    - Smarty
    - Twig
    - Blade
    - Plates


One of the main goals for this project was to have a minimal core, without dependencies, 
compliant with PSRs and allow every oen using it, use what they are familiar with in terms
of additional packages without having to create wrappers/adapters framework.

## Requirements

 1. PHP 7.0+
 2. php.ini (Optional but recommended while development)
    - `zend.assertions=1`
    - `assert.exceptions=1`
 
The php.ini configuration is suggested only while development, so that some errors, which 
could happen only while developing (misspelled array key, invalid configuration, etc.) are
not checked all the time while running on production environment - squeezing as much 
performance as possible.

For a quick start you can clone [`onion/bootstrap`](https://github.com/phOnion/bootstrap) and 
see how the basic setup looks like and play around to test the features available.

## Configuration

The approach taken for setting up the framework is more configuration over convention
because everyone has different code-style and different tastes, there is no point in 
enforcing a specific way to code something so that it works, without providing any 
other way. So there are some configurations for some components and they are explained
in their respectful sections of this documentation.
