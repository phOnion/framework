<?php declare(strict_types=1);
namespace Onion\Framework\Http\Factory;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Container\ContainerInterface;
use function GuzzleHttp\Psr7\stream_for;
use function GuzzleHttp\Psr7\parse_header;
use Psr\Http\Message\ServerRequestInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

final class ServerRequestFactory implements FactoryInterface
{
    /**
     * Method that handles the construction of the object
     *
     * @param ContainerInterface $container DI Container
     *
     * @return ServerRequestInterface
     */
    public function build(ContainerInterface $container): ServerRequestInterface
    {
        return ServerRequest::fromGlobals();
    }
}
