# phOnion - lightweight PSR-7 framework

----


This is the official frameowork package that will power the family of 
modules available for the phOnion app (TBD :wip:). It is inspired by
`zend-expressive` and for the most parts should be compatible with basic
application written for it (*note that template systems are not 
handled by default ATM, so for using one you will need to actually 
handle the logic yourself.*)

The basic goal of this `fw` package is not to be yet another framework, 
but to provide modernized base for newer applications, the absence of 
template system is that, modern applications for the most part should 
use APIs instead of the plain old way, where the page gets recycled 
after every request (not advocating for SPAs, some actions do not need
page reload!!!)


To get started you will need to either use the 
`composer create-project onion/meta`, which will take care of installing
framework dependencies, composer plugin as well as the configuration 
files and what not; or alternatively check the gists provided and 
copy-paste them to bootstrap the basic application, which will be 
able to respond your requests. 

And that is pretty much it, you can start writing your application in 
minutes.
