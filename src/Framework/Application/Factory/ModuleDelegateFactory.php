<?php
declare(strict_types = 1);
namespace Onion\Framework\Application\Factory;

use Interop\Container\ContainerInterface;
use Interop\Http\Middleware\DelegateInterface;
use Onion\Framework\Application\Interfaces\ModuleInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\Delegate;

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
class ModuleDelegateFactory implements FactoryInterface
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
         * @var array[][] $middleware
         */
        $middleware = $container->get('middleware');

        foreach ($middleware as $handler) {
            if ($handler === 'modules') {
                $delegate = $this->getModulesDelegate($container, $delegate);
                continue;
            }
            $delegate = new Delegate($container->get($handler), $delegate);
        }

        return $delegate;
    }

    private function getModulesDelegate(ContainerInterface $container, DelegateInterface $delegate = null): DelegateInterface
    {
        foreach ($container->get('modules') as $moduleClass) {
            /**
             * @var ModuleInterface
             */
            $module = $container->get($moduleClass);
            assert(
                $module instanceof ModuleInterface,
                "Class $moduleClass needs to implement Application\\ModuleInterface"
            );

            $delegate = new Delegate($module->build($container), $delegate);
        }

        return $delegate;
    }
}
