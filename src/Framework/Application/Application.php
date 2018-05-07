<?php declare(strict_types=1);
namespace Onion\Framework\Application;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\StreamWrapper;
use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
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

    /** @var string */
    private $baseAuthorization;

    /** @var string */
    private $proxyAuthorization;

    /**
     * Application constructor.
     *
     * @param RouteInterface[] $routes Array of routes that are supported
     */
    public function __construct(
        iterable $routes,
        RequestHandlerInterface $rootHandler = null,
        $baseAuthorizationType = 'bearer',
        $proxyAuthorizationType = 'digest'
    ) {
        $this->routes = $routes;
        $this->requestHandler = $rootHandler;
        $this->baseAuthorization = ucfirst($baseAuthorizationType);
        $this->proxyAuthorization = ucfirst($proxyAuthorizationType);
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
        try {
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
        } catch (MissingHeaderException $ex) {
            $headers = [];
            switch (strtolower($ex->getMessage())) {
                case 'authorization':
                    $status = 401;
                    $headers['WWW-Authenticate'] =
                        "{$this->baseAuthorization} realm=\"{$request->getUri()->getHost()}\" charset=\"UTF-8\"";
                    break;
                case 'proxy-authorization':
                    $status = 407;
                    $headers['Proxy-Authenticate'] =
                        "{$this->proxyAuthorization} realm=\"{$request->getUri()->getHost()}\" charset=\"UTF-8\"";
                    break;
                case 'if-match':
                case 'if-none-match':
                case 'if-modified-since':
                case 'if-unmodified-since':
                case 'if-range':
                    $status = 428;
                    break;
                default:
                    $status = 400;
                    break;
            }
            return new Response($status, $headers);
        } catch (NotFoundException $ex) {
            return new Response(404);
        } catch (MethodNotAllowedException $ex) {
            return (new Response(405))
                ->withHeader('Allowed', $ex->getAllowedMethods());
        } catch (\Throwable $ex) {
            return (new Response(in_array($request->getMethod(), ['get', 'head']) ? 503 : 501));
        }
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
