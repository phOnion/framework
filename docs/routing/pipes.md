In middleware world, there is the concept of piping (Zend Stratigility).
Pipes serve as a wildcard wildcard catch-group type of routes and while 
there is no helper method defined, currently the same functionality is 
possible when using the `Regex` route matcher and the catch-all routes.

If you are looking to create a pipe for all requests to `/users/`, you can 
define your pattern like `/users/*` and then pass it to a module that will 
handle that group. Note that in this scenario, the module is aware of the
prefix you should keep that in mind. Also there are more complex cases which 
you can also handle using this kind of pipes. Say for example you have a blog
site and all article titles are within the url (SEO-friendly), but you want to 
show a different page, instead of the default 404, here you can play with the
with a pipe and with the catch-all, you just need to define your pipe for 
`/articles/*` and then as a last route, define it again, but this time with a
middleware retuning the desired result, instead of showing a plain old 404 page
(an idea might be to check the possible path and try make suggestions).
