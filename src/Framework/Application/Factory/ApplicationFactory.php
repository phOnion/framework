<?php declare(strict_types=1);
namespace Onion\Framework\Application\Factory;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Onion\Framework\Application\Application;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Zend\Diactoros\Response\EmitterInterface;

/**
 * A factory class solely responsible for assembling the Application
 * object that is used as the entrypoint to all application
 * functionality. It represents the minimal requirements to assemble
 * a fully fledged application be it with or without modules used
 *
 * @package Onion\Framework\Application\Factory
 */
final class ApplicationFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     *
     * @return Application
     */
    public function build(ContainerInterface $container): Application
    {
        return new Application(
            $container->get(DelegateInterface::class),
            $container->get(EmitterInterface::class)
        );
    }
}
