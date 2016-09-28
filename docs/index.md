# Getting Started

Hey, glad to see you there!

This is a minimalistic framework that was largely inspired by 
`zend-expressive` (you should check it out), the purpose of 
this project is to pretty much get on the train and start using 
newer packages, newer language features and to, well, start the 
transition from 5.3 compatibility and move it all the way up to 
5.6, which is a LTS version. 
[PHP Supported versions](http://php.net/supported-versions.php)

-----

***If you are the tinker kind, stop reading and clone the `quickstart` 
repo `git clone https://github.com/phOnion/quickstart` it is documented 
on the specifics of building an application, other than that you're on 
your own. If you want to get understanding and some slight reasoning 
with the decisions made feel free to continue reading and go through 
the tutorial section that will explain everything you need to know.***

-----

In the context of the framework we will use the terms *module* and 
*component*, each of which have a very special meaning.

## Module

A module is like an alias to a library (composer package), the only 
difference is that it is intended to work specifically with the 
framework, hence it is moved to another directory outside of the 
`vendor` dir. They are not intended to be used widely (although it is
encouraged to design/develop packages in a way that allows others to 
benefit from your work - don't vendor-lock people). Also a module 
must not provide any configuration files (hence the available 
flexibility)


## Component

A component is aimed more at the application level, rather than the
framework level, but it can be used as well to distribute packages that
extend the framework. The only difference is that the component's 
config files get symbolic links that are loaded by the config loader
and end up loaded inside the main app (note that you, as a developer,
are responsible for the content's of the modules you use, develop and/or
maintain.
