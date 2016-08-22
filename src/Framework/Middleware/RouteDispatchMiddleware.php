<?php
/**
 * PHP Version 5.6.0
 *
 * @category Routing
 * @package  Onion\Framework\Middleware
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Middleware;

use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\ServerMiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Onion\Framework\Interfaces\Router\RouterInterface;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Psr\Http\Message;
use Zend\Diactoros\Response\EmptyResponse;

class RouteDispatchMiddleware implements ServerMiddlewareInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var StackInterface
     */
    protected $middleware;

    /**
     * RouteDispatchMiddleware constructor.
     *
     * @param RouterInterface           $routerInterface
     * @param StackInterface $chain
     */
    public function __construct(RouterInterface $routerInterface, StackInterface $chain)
    {
        $this->router = $routerInterface;
        $this->middleware = $chain;
    }

    public function handle(
        Message\ServerRequestInterface $request,
        FrameInterface $frame = null
    ) {
        try {
            $middleware = $this->middleware;
            $route = $this->router->match($request->getMethod(), $request->getUri());

            foreach ($route->getParams() as $param => $value) {
                $request = $request->withAttribute($param, $value);
            }

            foreach ($route->getCallable() as $callable) {
                $middleware = $middleware->withMiddleware($callable);
            }

            return $middleware->handle($request);
        } catch (NotFoundException $ex) {
            if ($frame !== null) {
                return $frame->next($request->withAttribute('exception', $ex))
                    ->withStatus(404);
            }
        } catch (MethodNotAllowedException $ex) {
            if ($frame !== null) {
                return $frame->next($request->withAttribute('exception', $ex))
                    ->withStatus(405)
                    ->withHeader('Allowed', implode(', ', $ex->getAllowedMethods()));
            }
        } catch (\Exception $ex) {
            if ($frame !== null) {
                return $frame->next($request->withAttribute('exception', $ex))
                    ->withStatus(500);
            }
        }

        return new EmptyResponse(500);
    }
}
