Container delegation is part of the spec but there are not any specifics
on how the interface has to look, so for the purpose of not to do voodoo
there is the `AttachableContainer` interface, that the child containers
need to implement in order to allow injecting of the parent container inside.

This might not seem to be the prettiest solution but will allow for
some way to pass the delegate container to the child and preserve the
required way of working with the container validation:

[See this document for more information about Container Delegation](https://github.com/container-interop/container-interop/blob/master/docs/Delegate-lookup.md)
