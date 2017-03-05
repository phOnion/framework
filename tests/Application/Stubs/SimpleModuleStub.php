<?php
declare(strict_types=1);
namespace Tests\Application\Stubs;

use Psr\Container\ContainerInterface;
use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Onion\Framework\Application\Interfaces\ModuleInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Zend\Diactoros\Response\EmitterInterface;

class SimpleModuleStub implements ModuleInterface
{
    public function build(ContainerInterface $container): ApplicationInterface
    {
        return new Application(new Delegate(new MiddlewareStub()), $container->get(EmitterInterface::class));
    }
}
