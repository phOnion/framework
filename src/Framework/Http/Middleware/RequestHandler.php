<?php declare(strict_types=1);
namespace Onion\Framework\Http\Middleware;

use Psr\Http\Message;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestHandler implements RequestHandlerInterface
{
    /** @var MiddlewareInterface */
    protected $middleware;

    /** @var Message\ResponseInterface */
    protected $response;

    /**
     * @param \Iterator $middleware Middleware of the frame
     */
    public function __construct(\Iterator $middleware, Message\ResponseInterface $response = null)
    {
        $this->middleware = $middleware;
        $this->response = $response;
    }

    /**
     * @param Message\ServerRequestInterface $request
     *
     * @throws \RuntimeException If asked to return the response template, but the template is empty
     * @return Message\ResponseInterface
     */
    public function handle(Message\ServerRequestInterface $request): Message\ResponseInterface
    {
        if ($this->middleware->valid()) {
            $middleware = $this->middleware->current();
            $this->middleware->next();

            return $middleware->process($request, $this);
        }

        if (null === $this->response) {
            throw new \RuntimeException('No base response provided');
        }

        return $this->response;
    }
}
