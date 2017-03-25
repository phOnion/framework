The hydratables are traits which provide you the ability to "inject" data within objects that are initialized, but please note that those preserve the class' immutability and as such return new instance on every hydration. While there is an interface defining the `hydrate` and `extract` methods, there are 2 traits providing the 2 most common strategies for hydration:

 - `Method` - uses getters/setters
 - `Property` - uses public properties

Both hydration strategies perform transformation on the keys provided, so the keys 
of the array get translated from `snake_case` to `cammelCase`, so if you provide an array like `['username' => 'foo', 'first_name' => 'John']` the Method strategy will attempt to use `setUsername` and `setFirstName` in order to hydrate the object; if the Property strategy is used it will look for `username` and `firstName` public properties on the object.

Both will preserve immutability of the hydrated object so you should keep that in mind
