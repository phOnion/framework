<?php
declare(strict_types = 1);
namespace Onion\Framework\Application\Factory;

use Interop\Container\ContainerInterface;
use Interop\Http\Middleware\DelegateInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Onion\Framework\Router\Matchers\Prefix;
use Onion\Framework\Router\Parsers\Flat;
use Onion\Framework\Router\Router;

/**
 * A factory, very similar to GlobalDelegateFactory except that
 * this one actually instantiates a router, that is responsible
 * for routing the correct requests to the correct modules.
 * And from there on the Module/Application should handle its
 * internal routing independently from the main application.
 *
 *
 * @package Onion\Framework\Application\Factory
 */
class ModuleDelegateFactory extends GlobalDelegateFactory
{
    /**
     * @var array
     */
    private $methods = [];

    /**
     * Initializes an array with all HTTP methods to
     * pass for each route when creating a module
     */
    public function __construct()
    {
        $this->methods = [
            'GET',
            'HEAD',
            'POST',
            'PUT',
            'PATCH',
            'OPTIONS',
            'DELETE',
            'TRACE'
        ];
    }

    /**
     * @param ContainerInterface $container
     *
     * @throws \InvalidArgumentException
     *
     * @return DelegateInterface
     */
    public function build(ContainerInterface $container): DelegateInterface
    {
        $delegate = parent::build($container);
        $router = new Router(new Flat(), new Prefix());

        assert($container->has('modules'), 'No modules available in container, check configuration');

        foreach ($container->get('modules') as $prefix => $moduleClass) {
            $router->addRoute(
                $prefix,
                new Delegate($container->get($moduleClass), $delegate),
                $this->methods
            );
        }

        return new Delegate($router, $delegate);
    }
}
