<?php declare(strict_types=1);
namespace Onion\Framework\Application\Factory;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Application\Application;
use Onion\Framework\Collection\CallbackCollection;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\RequestHandler;
use Onion\Framework\Log\VoidLogger;
use Onion\Framework\Router\RegexRoute;
use Onion\Framework\Router\Interfaces\RouteInterface as Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;

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
        $routeCallback = function (array $route) use ($container): RouteInterface {
            $className = RegexRoute::class;
            if (isset($route['class'])) {
                $className = $route['class'];
            }

            $routeObject = new $className($route['pattern'], $route['name'] ?? null);
            if (isset($route['methods'])) {
                $routeObject = $routeObject->withMethods(array_map('strtoupper', $route['methods']));
            }

            if (isset($route['headers'])) {
                $routeObject = $routeObject->withHeaders($route['headers']);
            }

            if (isset($route['request_handler'])) {
                return $routeObject->withRequestHandler($container->get($route['request_handler']));
            }

            $middlewareGenerator = function () use ($route, $container): \Generator {
                $stack = array_merge(
                    ($container->has('middleware') ? $container->get('middleware') : []),
                    $route['middleware']
                );
                foreach ($stack as $middleware) {
                    yield $container->get($middleware);
                }
            };

            return $routeObject->withRequestHandler(new RequestHandler(
                $middlewareGenerator(),
                $container->has(ResponseInterface::class) ?
                    $container->get(ResponseInterface::class) : new Response()
            ));
        };

        $routes = new CallbackCollection($container->get('routes'), $routeCallback);
        $app = new Application(
            $routes,
            $container->has('application.authorization.base') ?
                $container->get('application.authorization.base') : '',
            $container->has('application.authorization.proxy') ?
                $container->get('application.authorization.proxy') : ''
        );

        return $app;
    }
}
