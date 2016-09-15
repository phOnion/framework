
## Information
This is the setup stage in which we'll go through the directory 
structure and some other details around the setup of the app we
will be building.

*(This guide will assume you know what Composer is and have `composer`
in your path nad the command is globally available)*

Assuming you usually use `src/` and `public/` in your projects,
the project structure is pretty straight forward except for some minor 
things which you shouldn't pay too much attention to for now.

This section uses the term "component" which is more like a structural
separation than anything fancy. There is no special way to register 
components, except maybe for the configuration part, but that depends 
entirely on your opinion.

-----

So here is the directory structure of you project at least initially:

```
 your-onion-app-directory
 |- config
 |  |- autoload/
 |  |- app/
 |  config.php
 |  container.php
 |- logs/
 |- public/
 |  index.php
 |- src/
 |  |- Demo
 |  |  |- Controllers
 |  |     Index.php
 |  |     Ping.php
 |  |  |- Middleware
 |  |     Signature.php
 |- vendor
 composer.json
```

Starting from top to bottom there is the `config` dir that contains
all configuration files for our application, in it there are `autoload/`
and `app/`. 

  - `autoload/` - holds the files which should always be loaded by the 
application such as core configuration (that we'll add in the next step)
as well as some

  - `app/` is a directory that will contain the configurations required
by our component.

There is one specific thing about the configuration files naming, namely
they end with `.global.php` or `.(something).php`, that is intentional 
and required convention in order to allow loading configurations 
per-environment. the `global`s are loaded on every request regardless of
environment, others are determined based on the environment variable
`ONION_ENVIRONMENT` if not present it is set to `dev` for development,
obviously you can change that to whatever you would like based on your 
preference but for the sake of simplicity, we'll refer to the defaults.

---

## Configuration

Open `composer.json` with your favorite editor and copy the below
contents into it, if you're using existing file adjust to require 
all dependencies and provide `psr-4` autoloading.

```
{
  "require": {
    "roave/security": "dev-master",
    "onion/framework": "1.0.0-alpha || ^1.0",
  },
  "autoload": {
    "psr-4": {
      "App\Demo": "src/"
    }
  }
}
```

Now run `composer update` and let it finish, you should not have any 
issues and the installation should complete fairly quickly.

now using the gist below populate the files that we have in the 
directory structure shown above or update accordingly to match your 
layout.

`config/config.php`:
```
<?php
/**
 * PHP Version 5.6.0
 *
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 */
$configurations = [];
$configurationIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__)
);
$envSuffix = '.' . getenv('ONION_ENVIRONMENT') . '.php';
while ($configurationIterator->valid()) {
    /**
     * @var \RecursiveDirectoryIterator $configurationIterator
     */
    if ($configurationIterator->isFile() && $configurationIterator->getExtension() === 'php') {
        if (strpos($configurationIterator->getFilename(), '.global.php') > 0 || 
            strpos($configurationIterator->getFilename(), $envSuffix) > 0) {
            
            $fileConfiguration = include $configurationIterator->getRealPath();
            if (!is_array($fileConfiguration)) {
                throw new \InvalidArgumentException(sprintf(
                    'The file "%s" does not exist or does not return an array',
                    $configurationIterator->getRealPath()
                ));
            }
            $configurations[] = $fileConfiguration;
        }
    }
    $configurationIterator->next();
}
return call_user_func_array('array_merge_recursive', $configurations);
```

`config/container.php`:
```
<?php
/**
 * PHP Version 5.6.0
 *
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 */
$configurations = new ArrayObject(require __DIR__ . '/config.php'); // Change if you use different file name
$configurations['dependencies']['shared'][\Onion\Framework\Configuration::class]
    = new \Onion\Framework\Configuration($configurations);
return new \Onion\Framework\Dependency\Container(
    array_key_exists('dependencies', $configurations) ?
        $configurations['dependencies'] : []
);
```

and finally `public/index.php`:
```
<?php
/**
 * PHP Version 5.6.0
 *
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 */
require_once __DIR__ . '/../vendor/autoload.php';
if (getenv('ONION_ENVIRONMENT') === false) {
    putenv('ONION_ENVIRONMENT=dev'); // Note the "dev" value, update if you want to use something else for prefix of development environment files
}
/**
 * @var \Interop\Container\ContainerInterface $container
 */
$container = include __DIR__ . '/../config/container.php';
/**
 * @var Onion\Framework\Application $application
 */
$application = $container->get(Onion\Framework\Application::class);
$application->run($container->get(Psr\Http\Message\ServerRequestInterface::class));
```
