## About

This is a minimalistic PSR-2, PSR-4, PSR-7, PSR-11, PSR-15 compliant 
framework intended to provide the absolute minimum for writing a
web application that utilizes the middleware concept as well as other 
accepted standards from the community. There are no plans at least for 
now to provide functionality that will allow usages other than web 
oriented.

It implements the PSR-15 spec (`http-interop/http-middleware: 0.2`) as
it is the latest present.

## Foreword

The drive behind the project was to write cleaner code utilizing php7
features + standards + learning new stuff and I think that it is worth
sharing, if not for people building the world's next best application at
least to help those, like me, who are looking in to get new idea, 
understand some things or in general see how some things can be done or
at the worst case scenario, how NOT to do certain stuff :D.

## Development tips

In the development environment and when testing the php.ini directives
`zend.assertions` and `assert.exceptions` should both be set to 1, since
the internals of the framework actually utilise the exceptions to warn 
about development mistakes, but are intended to be turned off on 
production in order to squeeze maximum performance out of the framework
by disabling some checks. An example of such is the container that will 
throw when a dependency does not match it's key type (if class/interface
name is used) and when a factory does not implement `FactoryInterface`,
when assertions are on, but will fail, if they are disabled, since those
are not things that should be affected by user input or any other input 
for that matter (they are developer made mistakes).

## Dependency Injection

The framework comes with a capable DI container that can resolve 
dependencies using a combination of a interface-class map, class-factory 
map and also reflection-based type resolution. These should be enough
for any developer to achieve everything necessary without relying on 
external sources, such as JSON, XML or annotations (although any usage 
in combination with annotations/AOP is encouraged, but not without a 
very good reason to be used, since it can make the code very cryptic,
error prone and hard to understand/maintain/pickup for others.

## Routing

Also a routing component is presented as well, which can be altered 
without changing the actual implementation through the use of 
parsers and matchers:

  - **Parsers** - They are responsible to prepare the route to be 
  processed later when matching against the current request URI.
  
  - **Matchers** - are responsible for making the heavy lifting in terms 
  of extracting parameters if applicable and determining if the current 
  request URI matches the offered pattern
