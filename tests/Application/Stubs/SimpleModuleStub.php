<?php
declare(strict_types=1);
namespace Tests\Application\Stubs;

use Interop\Container\ContainerInterface;
use Onion\Framework\Application\Application;
use Onion\Framework\Application\Interfaces\ModuleInterface;
use Onion\Framework\Http\Middleware\Delegate;
use Zend\Diactoros\Response\EmitterInterface;

class SimpleModuleStub implements ModuleInterface
{
    public function build(ContainerInterface $container): Application
    {
        return new Application(new Delegate(new MiddlewareStub()), $container->get(EmitterInterface::class));
    }
}
