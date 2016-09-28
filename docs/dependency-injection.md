## Intro

The framework comes with a minimal DI container implementation which
implements the `Interop\Container\ContainerInterface` in full, but 
however does not implement at all the recommendation about the 
`delegate container` (others do these kind of tasks better), it expects 
to receive an array with one of the following mandatory keys: 
'invokables' or 'factories', which are very similar to what 
zend-expressive uses, and therefor may be compatible with config files 
intended for ZF-Expressive, but note that nothing more than that is 
supported, nor is a goal to be interchangeable, but those 2 are at the 
moment.

**Invokables** is a very simple type of dependency, which can be summed
up to `'identifier' => new MyObject(),`, as this is what it does 
basically.

**Factories** is kind of simple object, but instead of just invoking the
indicated class, that class ***MUST*** implement 
`Onion\Framework\Interfaces\ObjectFactoryInterface` to make a contract
between itself and the container, and the container can be sure that no
unexpected behaviour will be encountered. Also if the identifier used is
a class name, the factory's result will be type-checked against that 
class name and will error if it does not extend/implement it.

By default all definitions, get created on every request to the 
container. To share dependencies there is the special `shared` entry,
which is always optional. If you want to have an object shared, you 
must provide it's identifier in invokables/factories and it will be 
shared after initialized. This is especially useful when applied to 
connection objects (database, cache, etc.) as a single connection will
be used, not a new per dependent object.

## Configuration

An example configuration file that allows registers
