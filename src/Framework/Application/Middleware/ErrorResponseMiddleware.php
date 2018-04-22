<?php declare(strict_types=1);
namespace Onion\Framework\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Onion\Framework\Router\Exceptions\NotFoundException;
use GuzzleHttp\Psr7\Response;
use Onion\Framework\Router\Exceptions\MethodNotAllowedException;
use Onion\Framework\Router\Exceptions\MissingHeaderException;

class ErrorResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (MissingHeaderException $ex) {
            return new Response(400);
        } catch (NotFoundException $ex) {
            return new Response(404);
        } catch (MethodNotAllowedException $ex) {
            return (new Response(405))
                ->withHeader('Allowed', $ex->getAllowedMethods());
        } catch (\Throwable $ex) {
            return (new Response(500));
        }
    }
}
