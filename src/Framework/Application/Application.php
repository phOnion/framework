<?php declare(strict_types=1);
namespace Onion\Framework\Application;

use GuzzleHttp\Psr7\StreamWrapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;

/**
 * Class Application
 *
 * @package Onion\Framework\Application
 */
class Application implements ApplicationInterface
{
    /**
     * @var RouteInterface[]
     */
    protected $routes = [];

    /**
     * Application constructor.
     *
     * @param RouteInterface[] $routes Array of routes that are supported
     */
    public function __construct(iterable $routes)
    {
        $this->routes = $routes;
    }

    /**
     * "Run" the application. Triggers the requestHandler
     * provided and when a response is returned it
     * passes it to the emitter for final processing
     * before sending it to the client
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    public function run(ServerRequestInterface $request): void
    {
        /** @var ResponseInterface $response */
        $response = $this->handle($request);

        if (!$this->hasPreviousOutput()) {
            $status = $response->getStatusCode();
            $reasonPhrase = $response->getReasonPhrase();
            header(
                "HTTP/{$response->getProtocolVersion()} {$status} {$reasonPhrase}",
                true,
                $status
            );

            foreach ($response->getHeaders() as $header => $values) {
                foreach ($values as $index => $value) {
                    header("{$header}: {$value}", $index === 0);
                }
            }
        }

        stream_copy_to_stream(
            StreamWrapper::getResource($response->getBody()),
            fopen('php://output', 'wb')
        );
    }

    /**
     * Triggers processing of the provided requestHandler,
     * without emitting the response. Useful in the
     * context of the application running as a module
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->routes as $route) {
            if ($route->isMatch($request->getUri()->getPath())) {
                if (!$route->hasMethod($request->getMethod())) {
                    throw new MethodNotAllowedException($route->getMethods());
                }

                foreach ($route->getParameters() as $attr => $value) {
                    $request = $request->withAttribute($attr, $value);
                }

                return $route->handle($request);
            }
        }

        throw new NotFoundException(
            "No route available to handle '{$request->getUri()->getPath()}'"
        );
    }

    /**
     * Helper to check if output has been sent to the client
     *
     * @return bool
     */
    private function hasPreviousOutput(): bool
    {
        return !headers_sent() && (ob_get_level() === 0 && ob_get_length() === 0);
    }
}
