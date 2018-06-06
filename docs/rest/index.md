# Introduction

Although there is nothing REST-specific in the core package, there
is `onion/rest` that aims to allow more or less transparent conversion
from entity to appropriate REST representation according to a given standard.

But in order to simplify the development and bring something along the
lines of 'Controller' as in MVC-pattern there is the abstract class:
`Onion\Framework\Controller\Controller` it is a small pass through wrapper
around your code that will do (request method) to (code method) translation
and do some small handling to conform with the request methods. Like invoking
`get` for `HEAD` requests if no designated method is present and remove the
response body.
