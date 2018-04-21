<?php
declare(strict_types=1);
namespace Tests\Application\Stubs;

use Psr\Container\ContainerInterface;
use Onion\Framework\Application\Application;
use Onion\Framework\Http\Middleware\RequestHandler;
use Onion\Framework\Application\Interfaces\ModuleInterface;
use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SimpleModuleStub implements ModuleInterface
{
    public function build(ContainerInterface $container): RequestHandlerInterface
    {
        return new Application(new RequestHandler(new MiddlewareStub()));
    }
}
