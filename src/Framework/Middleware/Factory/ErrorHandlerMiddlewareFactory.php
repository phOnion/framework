<?php
/**
 * PHP Version 5.6.0
 *
 * @category Object-Factory
 * @package  Onion\Framework\Middleware\Factory
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Middleware\Factory;

use Interop\Container\ContainerInterface;
use Onion\Framework\Configuration;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Onion\Framework\Interfaces\ObjectFactoryInterface;
use Onion\Framework\Middleware\ErrorHandlerMiddleware;

class ErrorHandlerMiddlewareFactory implements ObjectFactoryInterface
{
    /**
     * @param ContainerInterface $container
     *
     * @return ErrorHandlerMiddleware
     * @throws \RuntimeException
     */
    public function __invoke(ContainerInterface $container)
    {
        /**
         * @var Configuration $configuration
         */
        $configuration = $container->get(Configuration::class);

        /**
         * @var StackInterface $stack
         */
        $stack = $container->get(StackInterface::class);

        if (!$configuration->has('error_handlers')) {
            throw new \RuntimeException(
                'No configuration entry "error_handlers" available, unable to build error handler'
            );
        }

        foreach ($configuration->get('error_handlers') as $handler) {
            $stack = $stack->withMiddleware($container->get($handler));
        }

        return new ErrorHandlerMiddleware($stack);
    }
}
