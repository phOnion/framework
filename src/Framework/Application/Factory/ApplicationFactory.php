<?php declare(strict_types=1);
namespace Onion\Framework\Application\Factory;

use Onion\Framework\Application\Application;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Http\Middleware\RequestHandler;
use Onion\Framework\Router\RegexRoute;
use Onion\Framework\Router\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;
use Onion\Framework\Collection\CallbackCollection;

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
        $routeCallback = function ($route) use ($container) {
            $className = RegexRoute::class;
            if (isset($route['class'])) {
                $className = $route['class'];
            }

            $r = new $className($route['pattern'], $route['name'] ?? null);
            if (isset($route['methods'])) {
                $r = $r->withMethods(array_map('strtoupper', $route['methods']));
            }

            if ($r instanceof Route && isset($route['headers'])) {
                $r = $r->withHeaders($route['headers']);
            }

            if (isset($route['request_handler'])) {
                return $r->withRequestHandler($container->get($route['request_handler']));
            }

            $middlewareGenerator = function () use ($route, $container) {
                $stack = array_merge(
                    ($container->has('middleware') ? $container->get('middleware') : []),
                    $route['middleware']
                );
                foreach ($stack as $middleware) {
                    yield $container->get($middleware);
                }
            };

            return $r->withRequestHandler(new RequestHandler(
                $middlewareGenerator(),
                $container->has(ResponseInterface::class) ?
                    $container->get(ResponseInterface::class) : new Response()
            ));
        };

        $routes = new CallbackCollection($container->get('routes'), $routeCallback);
        return new Application(
            $routes,
            $container->has(RequestHandlerInterface::class) ?
                $container->get(RequestHandlerInterface::class) : null
        );
    }
}
