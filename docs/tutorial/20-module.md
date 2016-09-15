## Components

In the previous part you saw that there are a few different kinds of 
dependencies, how to define them and how to define global middleware.

Now lets talk a little more about components. They as said in the 
introduction are not anything special, nor you need to do anything 
special to enable or configure them at all, they are self registering
and is the responsibility of the `config/config.php` to take care of 
that ***without*** any special change, it should happen by loading the 
configuration files located in the `config/*component_name*` directory.

It is advisable to use a folder named after the component for it's 
configuration in order to allow change of configs independently from
the main application files or from the files of other components. Other 
than that, everything applies, file extensions must end in either 
`.global.php` or `.$ONION_ENVIRONMENT.php` to separate different 
environments.

## Controllers
At the moment our application is a blank shell (which is the minimal 
requirement in order to start an application using the framework (Neat
isn't it?). 

Now lets open the `src/Demo/Controllers/Index.php` and write our
first controller that will display a message. 

```
<?php
/**
 * PHP Version 5.6.0
 *
 * @category Controllers
 * @package  App\Demo\Controllers
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 */
namespace App\Demo\Controllers;

use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message;
use Zend\Diactoros\Response\HtmlResponse;

class Home implements ServerMiddlewareInterface
{
    public function process(Message\ServerRequestInterface $request, FrameInterface $frame = null)
    {
        return new HtmlResponse('<strong>Hello, World!</strong>');
    }
}
```

but why stop there, lets create another that will greet us (we are the 
devs, right?). Create a file named `Greeter.php` in `src/Demo/Controllers`.
Now paste the following:

```
<?php
/**
 * PHP Version 5.6.0
 *
 * @category Controllers
 * @package  App\Demo\Controllers
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 */
namespace App\Demo\Controllers;

use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message;
use Zend\Diactoros\Response\HtmlResponse;

class Home implements ServerMiddlewareInterface
{
    public function process(Message\ServerRequestInterface $request, FrameInterface $frame = null)
    {
        return new HtmlResponse(sprintf(
            'Well greetings, %s', ucfirst($request->getAttribute('name', 'stranger'))
        ));
    }
}
```

## Dependencies

Now that we have one controller, we must register it in the dependencies
in order for the application to know from where to get it. Create a file
in `config/app` named `dependencies.global.php` and paste in the 
following:

```
use App\Demo\Controllers as Controller;

return [
    'dependencies' => [
        'invocables' => [
            Controller\Home::class => Controller\Home::class,
            Controller\Greeter::class => Controller\Greeter::class
        ]
    ]
];
```

Now we have defined all the requirements that the application needs to 
invoke the controllers.

 > but wait.. How are we going to trigger it to invoke them?

you asked the right question! Continue ...

## Routes

Now we will make use of the router and will be able to define routes 
that will provide the necessary functionality to dispatch our 
controllers.

Create a file named `routes.global.php` in `config/app/` and add to it
the following:

```
use App\Demo\Controllers as Controller;
return [
    'routes' => [
        [
            'pattern' => '/',
            'middleware' => [Controller\Home::class]
        ],[
            'pattern' => '/greet[/[name:\p{L}]],
            'middleware' => [Controller\Greeter::class]
        ]
    ]
];
```

This is it folks! Now run the built-in php server like the following:
`php -S localhost:8081 -t public/ public/index.php` and open 
[this link](http://localhost:8081/) and if everything went well you will
be presented with a "Hello, World" message. But that is simply obvious
isn't it? Now go [here](http://localhost:8081/greet) and you should see
the "Now greetings, Stranger!" message, now to see the magic happen 
copy the following in to your browser address: 
`http://localhost:8081/greet` and `http://localhost:8081/greet/majesty` 
see what happens!
