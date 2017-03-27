<?php declare(strict_types = 1);
namespace Onion\Framework\Application\Factory;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Onion\Framework\Application\Interfaces\ModuleInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Onion\Framework\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

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
final class ModuleDelegateFactory implements FactoryInterface
{
    private $route;
    public function __construct()
    {
        $this->route = new Router\Route();
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
        assert(
            $container->has('modules'),
            'No modules available in container, check configuration'
        );

        /**
         * @var array[] $middleware
         */
        $middleware = $container->get('middleware');
        $stack = [];

        foreach ($middleware as $index => $handler) {
            if ($handler === 'modules') {
                $moduleMiddlewareStack = $this->getModulesStack($container);
                $router = new Router\Router(
                    new Router\Matchers\Regex()
                );

                foreach ($moduleMiddlewareStack as $prefix => $module) {
                    $router->addRoute($this->route->hydrate([
                            'pattern' => '/' . ltrim($prefix, '/') . '*',
                            'delegate' => new Delegate(
                                [$module],
                                $container->has(ResponseInterface::class) ?
                                    $container->get(ResponseInterface::class) : null
                            ),
                            'methods' => [
                                'GET', 'HEAD', 'POST', 'PUT', 'OPTIONS', 'DELETE', 'TRACE', 'CONNECT',
                            ],
                        ]));
                }

                $stack[] = $router;
                continue;
            }

            $stack[] = $container->get($handler);
        }

        return new Delegate(
            $stack,
            $container->has(ResponseInterface::class) ?
                $container->get(ResponseInterface::class) : null
        );
    }

    private function getModulesStack(ContainerInterface $container): array
    {
        $middlewareStack = [];
        foreach ($container->get('modules') as $prefix => $moduleClass) {
            /**
             * @var ModuleInterface
             */
            $module = $container->get($moduleClass);
            assert(
                $module instanceof ModuleInterface,
                "Class $moduleClass needs to implement Application\\ModuleInterface"
            );

            $middlewareStack[$prefix] = $module->build($container);
        }

        return $middlewareStack;
    }
}
