<?php declare(strict_types=1);
namespace Onion\Framework\Application\Factory;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This factory builds the defined global middleware
 * stack in executable order, it therefore is a vital
 * part of every application.
 *
 * It must not be used with modules, since the logic
 * behind that is actually combined with the module
 * delegate factory
 *
 * @see ModuleDelegateFactory
 *
 * @package Onion\Framework\Application\Factory
 */
final class GlobalDelegateFactory implements FactoryInterface
{

    /**
     * @param ContainerInterface $container
     *
     * @return DelegateInterface To be injected in the Application constructor
     */
    public function build(ContainerInterface $container): DelegateInterface
    {
        /**
         * @var array[][] $middleware
         */
        $middleware = $container->get('middleware');

        $stack = [];
        foreach ($middleware as $handler) {
            $stack[] = $container->get($handler);
        }

        return new Delegate(
            $stack,
            $container->has(ResponseInterface::class) ?
                $container->get(ResponseInterface::class) : null
        );
    }
}
