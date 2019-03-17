<?php declare(strict_types=1);
namespace Onion\Framework\Application\Factory;

use Onion\Framework\Application\Application;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A factory class solely responsible for assembling the Application
 * object that is used as the entry point to all application
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
    public function build(ContainerInterface $container): object
    {
        return new Application($container->get(RequestHandlerInterface::class));
    }
}
