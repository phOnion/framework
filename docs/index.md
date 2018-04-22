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
Being maily API oriented, now there is a package `onion/rest` that provides
a few classes that should simplify the generation of API-standard responses
as well as `onion/interfaces`'s `Onion\Framework\Rest` namespace has a couple
of interfaces aimed specifically at that.

In the latest release a lot of changes have been made, to note a few major ones

- PHP >= 7.2 is the minimal version required
- Many components that might increase the loading times have been made 'lazy' with the help of generators
- There is no router! Everything is oriented towards true middleware architecture, so middleware and request handlers are used heavily
- Route's now can be initialized using a custom classes instead of the old approach with matchers/parsers
- Modularity is now possible through combination of appropriate PrefixRoutes and Application (no more delegate classes)

Content Negotiation is something that is planned but since it is not so trivial task, it is postponed for future minor release

## Requirements

 1. PHP 7.2+
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
