## About

A minimal core framework that has been designed to provide the absolute minimum
of what is necessary to get an application up and running. Using `PSR-7`, `PSR-11` and `PSR-15`
you can plug in a lot of the code available in the wild to cover things that are not
available as part of this package. As of now this package doesn't aim to become a
full blown application framework, but rather allow for easy development of APIs.
And while there are many great frameworks that allow us to do that, I thought I
could share my take on the approach with the world (and I've learned a lot along
the way too!). So if you are interested in delving deeper in my silent madness,
feel free to check this and [other `onion` packages](https://packagist.org/packages/onion/)
and let me know what you think.

P.S: I know the documentation is not the best, but if you have an idea to
improve it I would be really happy to see your input (and a PR is always welcome).

## Requirements

1.  PHP 8.1+ 🎉
2.  php.ini (Optional but recommended while development)
    - `zend.assertions=1`
    - `assert.exceptions=1`

The ini configuration is a nice-to-have thing, because that way some checks
such as if the `middleware` key is defined and contains only valid middleware
items is redundant on production, because it should be caught during development
and testing (you do at least <kbd>F5</kbd> tests right?) and by doing so
these checks can save a millisecond or two

A quick-start application is coming soon-ish :)

## Configuration

The approach taken for setting up the framework is more configuration over convention
because everyone has different code-style and different tastes, there is no point in
enforcing a specific way to code something so that it works, without providing any
other way. So there are some configurations for some components and they are explained
in their respectful sections of this documentation.
