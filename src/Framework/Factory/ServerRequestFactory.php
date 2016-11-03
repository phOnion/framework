<?php
/**
 * PHP Version 5.6.0
 *
 * @category Object-Factory
 * @package  Onion\Framework\Factory
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Factory;

use Interop\Container\ContainerInterface;
use Onion\Framework\Interfaces\ObjectFactoryInterface;

class ServerRequestFactory implements ObjectFactoryInterface
{

    /**
     * Method that handles the construction of the object
     *
     * @param ContainerInterface $container DI Container
     *
     * @return \Zend\Diactoros\ServerRequest
     */
    public function __invoke(ContainerInterface $container)
    {
        return \Zend\Diactoros\ServerRequestFactory::fromGlobals();
    }
}
