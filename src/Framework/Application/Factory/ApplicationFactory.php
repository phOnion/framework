<?php declare(strict_types=1);
namespace Onion\Framework\Application\Factory;

use Psr\Container\ContainerInterface;
use Onion\Framework\Application\Application;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Router\RegexRoute;
use Onion\Framework\Http\Middleware\RequestHandler;

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
    public function build(ContainerInterface $container): object
    {
        $routeGenerator = function () use ($container) {
            $routes = $container->get('routes');
            foreach ($routes as $route) {
                $className = RegexRoute::class;
                if (isset($route['class'])) {
                    $className = $route['class'];
                }

                $r = new $className($route['pattern'], $route['name'] ?? null);
                if (isset($route['methods'])) {
                    $r = $r->withMethods(array_map('strtoupper', $route['methods']));
                }

                $middlewareGenerator = function () use ($route, $container) {
                    foreach ($route['middleware'] as $middleware) {
                        yield $container->get($middleware);
                    }
                };
                yield $r->withRequestHandler(new RequestHandler($middlewareGenerator()));
            }
        };

        return new Application($routeGenerator());
    }
}
