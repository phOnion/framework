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
use Onion\Framework\Application;
use Onion\Framework\Http\Middleware\Pipe;
use Onion\Framework\Interfaces;
use Zend\Diactoros\Response\EmitterInterface;

class ApplicationFactory implements Interfaces\ObjectFactoryInterface
{
    public function __invoke(ContainerInterface $container)
    {
        return new Application(
            $container->get(Pipe::class),
            $container->get(EmitterInterface::class)
        );
    }
}
