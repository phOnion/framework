<?php declare(strict_types=1);
namespace Onion\Framework\Http\Middleware\Factory;

use Onion\Framework\Router\Route;
use Psr\Container\ContainerInterface;
use Onion\Framework\Router\Interfaces\RouterInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\RequestHandler;

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

        $middlewareGenerator = function () use ($container) {
            $middleware = $container->get('middleware');

            foreach ($middleware as $identifier) {
                $instance = $container->get($identifier);
                assert(
                    is_object($instance) && $instance instanceof MiddlewareInterface,
                    new \TypeError("'{$identifier}' must implement MiddlewareInterface")
                );

                yield $instance;
            }
        };
        return new RequestHandler($middlewareGenerator());
    }
}
