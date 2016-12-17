<?php
declare(strict_types = 1);
namespace Onion\Framework\Application\Factory;

use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Onion\Framework\Application\Interfaces\ModuleInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Onion\Framework\Middleware\Internal\ModulePathStripperMiddleware;
use Onion\Framework\Router;
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
                    new Router\Parsers\Flat(),
                    new Router\Matchers\Prefix()
                );

                foreach ($moduleMiddlewareStack as $prefix => $module) {
                    $router->addRoute($prefix, new Delegate(
                            [new ModulePathStripperMiddleware($prefix), $module],
                            $container->has(ResponseInterface::class) ?
                                $container->get(ResponseInterface::class) : null
                        ), [
                        'GET', 'HEAD', 'POST', 'PUT', 'OPTIONS', 'DELETE', 'TRACE', 'CONNECT'
                    ]);
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
