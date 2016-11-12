<?php
declare(strict_types=1);
namespace Onion\Framework\Http\Factory;

use Interop\Container\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequestFactory implements FactoryInterface
{

    /**
     * Method that handles the construction of the object
     *
     * @param ContainerInterface $container DI Container
     *
     * @return \Zend\Diactoros\ServerRequest
     */
    public function build(ContainerInterface $container): ServerRequestInterface
    {
        return \Zend\Diactoros\ServerRequestFactory::fromGlobals();
    }
}
