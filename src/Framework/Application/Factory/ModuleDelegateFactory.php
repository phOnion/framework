<?php
declare(strict_types = 1);
namespace Onion\Framework\Application\Factory;

use Interop\Container\ContainerInterface;
use Interop\Http\Middleware\DelegateInterface;
use Onion\Framework\Application\Interfaces\ModuleInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Onion\Framework\Router;

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
        $delegate = null;

        assert(
            $container->has('modules'),
            'No modules available in container, check configuration'
        );

        /**
         * @var array[] $middleware
         */
        $middleware = $container->get('middleware');
        $stack = [];

        foreach ($middleware as $handler) {
            if ($handler === 'modules') {
                $stack = $this->getModulesStack($container, $delegate);

                continue;
            }

            $stack[] = $container->get($handler);
        }

        return new Delegate($stack);
    }

    private function getModulesStack(ContainerInterface $container): array
    {
        $middlewareStack = [];
        foreach ($container->get('modules') as $moduleClass) {
            /**
             * @var ModuleInterface
             */
            $module = $container->get($moduleClass);
            assert(
                $module instanceof ModuleInterface,
                "Class $moduleClass needs to implement Application\\ModuleInterface"
            );

            $middlewareStack[] = $module->build($container);
        }

        return $middlewareStack;
    }
}
