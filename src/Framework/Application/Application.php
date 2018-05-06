<?php declare(strict_types=1);
namespace Onion\Framework\Application;

use GuzzleHttp\Psr7\StreamWrapper;
use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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

    /** @var RequestHandlerInterface */
    private $requestHandler;

    /**
     * Application constructor.
     *
     * @param RouteInterface[] $routes Array of routes that are supported
     */
    public function __construct(iterable $routes, RequestHandlerInterface $rootHandler = null)
    {
        $this->routes = $routes;
        $this->requestHandler = $rootHandler;
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
        try {
            /** @var ResponseInterface $response */
            $response = $this->handle($request);
        } catch (\Throwable $exception) {
            if ($this->requestHandler === null) {
                throw $exception;
            }

            $response = $this->requestHandler->handle(
                $request->withAttribute('exception', $exception)
                    ->withAttribute('error', $exception)
            );
        } finally {
            if (isset($response) && !$this->hasPreviousOutput()) {
                $status = $response->getStatusCode();
                $reasonPhrase = $response->getReasonPhrase();
                header(
                    "HTTP/{$response->getProtocolVersion()} {$status} {$reasonPhrase}",
                    true,
                    $status
                );

                foreach ($response->getHeaders() as $header => $values) {
                    foreach ($values as $index => $value) {
                        if ($value === '') {
                            continue;
                        }

                        header("{$header}: {$value}", $index === 0);
                    }
                }

                file_put_contents('php://output', $response->getBody());
            }
        }
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
        $path = $request->getUri()->getPath();
        reset($this->routes);
        foreach ($this->routes as $route) {
            if ($route->isMatch($path)) {
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
