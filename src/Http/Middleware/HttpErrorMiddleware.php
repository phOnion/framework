<?php declare(strict_types=1);
namespace Onion\Framework\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Router\Exceptions\MissingHeaderException;
use Onion\Framework\Router\Interfaces\Exception\NotAllowedException;
use Onion\Framework\Router\Interfaces\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpErrorMiddleware implements MiddlewareInterface
{
    private $baseAuthorization;
    private $proxyAuthorization;

    public function __construct(string $baseAuth = 'bearer', string $proxyAuth = 'basic')
    {
        $this->baseAuthorization = $baseAuth;
        $this->proxyAuthorization = $proxyAuth;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (MissingHeaderException $ex) {
            $headers = [];
            switch ($ex->getHeaderName()) {
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
        } catch (NotAllowedException $ex) {
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
