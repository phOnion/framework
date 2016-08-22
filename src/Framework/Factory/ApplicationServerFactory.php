<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Onion\Framework\Factory
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Factory;

use Interop\Container\ContainerInterface;
use Onion\Framework\Configuration;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Onion\Framework\Interfaces\ObjectFactoryInterface;
use Onion\Framework\Interfaces\Application\MiddlewareRunnerInterface;
use Psr\Http\Message;
use Zend\Diactoros\Server;

class ApplicationServerFactory implements ObjectFactoryInterface
{

    /**
     * @param ContainerInterface $container
     *
     * @return Server
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __invoke(ContainerInterface $container)
    {
        /**
         * @var $middlewareStack array
         */
        $middlewareStack = $container->get(Configuration::class)
            ->get('middleware');

        /**
         * @var StackInterface $middleware
         */
        $middleware = $container->get(StackInterface::class);

        usort($middlewareStack, function ($stack1, $stack2) {
            if ($stack1['priority'] > $stack2['priority']) {
                return 1;
            }

            if ($stack1['priority'] < $stack2['priority']) {
                return -1;
            }

            return 0;
        });

        foreach ($middlewareStack as $definition) {
            /**
             * @var string[][] $definition
             */
            foreach ($definition['stack'] as $handler) {
                $middleware = $middleware->withMiddleware($container->get($handler));
            }
        }

        return $middleware;
    }
}
