<?php declare(strict_types=1);
namespace Onion\Framework\Application;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Router\Exceptions\NotFoundException;
use Onion\Framework\Router\Interfaces\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Application
 *
 * @package Onion\Framework\Application
 */
class Application implements ApplicationInterface
{
    /**
     * @var iterable
     */
    protected $routes = [];

    /** @var string */
    private $baseAuthorization;

    /** @var string */
    private $proxyAuthorization;

    /**
     * Application constructor.
     */
    public function __construct(
        iterable $routes,
        string $baseAuthType = 'bearer',
        string $proxyAuthType = 'digest'
    ) {
        $this->routes = $routes;
        $this->baseAuthorization = ucfirst($baseAuthType);
        $this->proxyAuthorization = ucfirst($proxyAuthType);
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
        /** @var ResponseInterface $response */
        $response = $this->handle($request);
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

        file_put_contents('php://output', $response->getBody());
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
            foreach ($this->routes as $route) {
                /** @var RouteInterface $route */
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
            switch (strtolower($ex->getHeaderName())) {
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
                ->withHeader('Allow', $ex->getAllowedMethods());
        } catch (\BadMethodCallException $ex) {
            return (new Response(
                in_array(strtolower($request->getMethod()), ['get', 'head']) ? 503 : 501
            ));
        } catch (\Throwable $ex) {
            return new Response(500);
        }
    }
}
