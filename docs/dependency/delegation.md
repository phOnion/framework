Container delegation is part of the spec but there are not any specifics
on how the interface should look like, so for the purpose of not to do voodoo
there is the `AttachableContainer` interface, that the child containers
need to implement in order to allow injecting of the parent container inside.

This might not seem to be the prettiest solution but will allow for
some way to pass the delegate container to the child and preserve the
required way of working with the container validation:

[See this document for more information about Container Delegation](https://github.com/container-interop/container-interop/blob/master/docs/Delegate-lookup.md)

After some changes in the way how the delegate container works, now it is
possible to return aggregated configuration. Say for example if there are
two containers that define 'list.foo', but with different values, like:
```
$c1 = new Container([
    'foo' => [
        'list' => [
            'bar' => 'baz',
        ],
    ],
]);

// And

$c2 = new Container([
    'foo' => [
        'list' => [
            'baz' => 'foo',
        ],
    ],
]);

// when retrieving from a delegate

$delegateContainer->get('foo.list'); // returns ['bar' => 'baz', 'baz' => 'foo']
```

This is alternative approach to the old behavior where only the first result
will be returned. This allows for greater flexibility like having `routes`
defined in many containers to be retrieved and instantiated through the same
container. Which comes especially useful when it comes to modules.
