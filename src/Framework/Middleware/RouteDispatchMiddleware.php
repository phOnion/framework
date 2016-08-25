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

use Onion\Framework\Interfaces\Common\PrototypeObject;
use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\ServerMiddlewareInterface;
use Onion\Framework\Interfaces\Middleware\StackInterface;
use Onion\Framework\Interfaces\Router\RouterInterface;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Psr\Http\Message;

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
     * @var StackInterface
     */
    protected $stack;

    /**
     * RouteDispatchMiddleware constructor.
     *
     * @param RouterInterface $routerInterface
     * @param StackInterface  $stack
     *
     * @internal param StackInterface $chain
     * @throws \InvalidArgumentException
     */
    public function __construct(RouterInterface $routerInterface, StackInterface $stack)
    {
        if (!$stack instanceof PrototypeObject) {
            throw new \InvalidArgumentException(
                'The supplied stack must implement Interfaces\Common\PrototypeInterface to allow ' .
                    'late injection of middleware'
            );
        }

        $this->router = $routerInterface;
        $this->stack = $stack;
    }

    public function process(
        Message\ServerRequestInterface $request,
        FrameInterface $frame = null
    ) {
        try {
            if (count($this->router) === 0) {
                throw new \LogicException('No routes added to router');
            }

            $route = $this->router->match($request->getMethod(), $request->getUri());

            foreach ($route->getParams() as $param => $value) {
                $request = $request->withAttribute($param, $value);
            }

            $this->stack->initialize($route->getMiddleware());

            return $this->stack->process($request, $frame);
        } catch (NotFoundException $ex) {
            if ($frame !== null) {
                return $frame->next($request->withAttribute('exception', $ex))
                    ->withStatus(404);
            }

            throw $ex;
        } catch (MethodNotAllowedException $ex) {
            if ($frame !== null) {
                return $frame->next($request->withAttribute('exception', $ex))
                    ->withStatus(405)
                    ->withHeader('Allowed', implode(', ', $ex->getAllowedMethods()));
            }

            throw $ex;
        } catch (\Exception $ex) {
            if ($frame !== null) {
                return $frame->next($request->withAttribute('exception', $ex))
                    ->withStatus(500);
            }

            throw $ex;
        }
    }
}
