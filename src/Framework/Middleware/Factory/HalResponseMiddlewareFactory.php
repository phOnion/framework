<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Onion\Framework\Middleware\Factory
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Middleware\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;
use Onion\Framework\Configuration;
use Onion\Framework\Interfaces\ObjectFactoryInterface;
use Onion\Framework\Middleware\HalResponseMiddleware;

class HalResponseMiddlewareFactory implements ObjectFactoryInterface
{

    /**
     * @param ContainerInterface $container
     *
     * @return HalResponseMiddleware
     * @throws \RuntimeException
     */
    public function __invoke(ContainerInterface $container)
    {
        try {
            /**
             * @var $config Configuration
             */
            $config = $container->get(Configuration::class);

            try {
                $halConfig = $config->get('hal');
                if (!array_key_exists('strategies', $halConfig)) {
                    throw new \RuntimeException(
                        'HAL Configuration exists, but does not define any strategies'
                    );
                }

                array_walk($halConfig['strategies'], function (&$value) use ($container) {
                    $value = $container->get($value);
                });

                return new HalResponseMiddleware($halConfig['strategies']);
            } catch (NotFoundException $ex) {
                throw new \RuntimeException('No configuration about HAL strategies found', null, $ex);
            }
        } catch (NotFoundException $ex) {
            throw new \RuntimeException(
                'No configuration object found in container, did you coded the init scripts properly',
                null,
                $ex
            );
        } catch (ContainerException $ex) {
            throw new \RuntimeException('Unexpected error occurred in the container', null, $ex);
        }
    }
}
