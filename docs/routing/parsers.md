Parsers are responsible for translating the route's path definition
to something that the matchers can understand and process. The ones provided by default are:

 - `Flat`: Extracts the path of the current request and returns it. The main purpose of the flat parser is to ensure only the path of the request is extracted in order to allow static routing (i.e one without any parameters, useful for small static sites).
 - `Regex`: This one is more complex as it adds the ability to have parameters inside the path. It translates a path definition to a parametrized regex should serve most cases for dynamic applications.
 
The flat parser does not have any specific semantics, but the regex (as you might have guessed) has. When defining the pattern,
you should denote the parameters in square braces `[]`, so a pattern for a user endpoint becomes `/users/[id]` this will extract the the value of `id` and set it as attribute of the Request object when it gets processed by the matcher and router.

If you need to provide constraints to your patterns you can use `:` after the parameter name and provide either a regex pattern or one of the `*` or `?` which map to `\w+` and `\w` respectively.

So `/users/[id:*]` will match `/users/a` as well as `/users/alpha`, but `/users/[id:?]` will match only `/users/a`.
But sometimes one might want to add a more specific pattern, like when the `id` can only be a number. This is also possible with the syntax above, just change the pattern to `/users/[id:\d+]` that way it will match only arguments that match the `\d+` regex.

And there is a special case for regex patterns that provide the catch-all functionality, its syntax is like the following
`/users*`, note that there is no `:` nor braces. This is the absolute wildcard so to speak. If you have a pattern 
`/users/*/[id:\d+]` this will match all sorts of paths, like: `/users/some/non/strict/path/with/id/5`


