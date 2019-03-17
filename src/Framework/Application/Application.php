<?php declare(strict_types=1);
namespace Onion\Framework\Application;

use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Application
 *
 * @package Onion\Framework\Application
 */
class Application implements ApplicationInterface
{
    /** @var RequestHandlerInterface */
    private $middleware;
    /**
     * Application constructor.
     */
    public function __construct(RequestHandlerInterface $middleware) {
        $this->middleware = $middleware;
    }

    /**
     * "Run" the application. Triggers the requestHandler
     * provided and when a response is returned it
     * passes it to the emitter for final processing
     * before sending it to the client
     *
     * @codeCoverageIgnore
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    public function run(ServerRequestInterface $request): void
    {
        $this->middleware->handle($request);
    }
}
