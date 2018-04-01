<?php declare(strict_types=1);
namespace Onion\Framework\Http\Middleware\Factory;

use Onion\Framework\Router\Route;
use Psr\Container\ContainerInterface;
use Onion\Framework\Router\Interfaces\RouterInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

class RequestHandlerFactory implements FactoryInterface
{
    /** @var Router */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function build(ContainerInterface $container)
    {
        if (!$container->has('middleware')) {
            throw new \RuntimeException(
                'Unable to initialize RequestHandler without defined middleware'
            );
        }

        $middleware = $container->get('middleware');
        $requestHandler = [];

        return $requestHandler;
    }
}
