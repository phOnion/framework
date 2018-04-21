When applications are running on production it is safe to assume that DI containers
can make relatively heavy use of caching so they can squeeze the maximum
performance. This is should not be any different. And to make this possible
it is not necessary to come up with your own solution. A caching (delegate)container
comes bundled inside the framework - `Onion\Framework\Dependency\CacheAwareContainer`.
In order to utilize it you just have to define a container factory (a class
implementing the `FactoryInterface`) and pass it as the first argument to the
cache-aware container, pass an instance of `Psr\SimpleCache\CacheInterface`
as the second one and you are ready to go.

By default that container will cache every dependency that it retrieves, but
if you have a factory that, for example checks for results in the database and
uses information from there to build the dependency on a per-user basis you
might not want to cache that result, here comes the third argument of the
container - blacklist. It allows you to specify a list of keys that should
not be cached and should always be retrieved from the delegated container.
