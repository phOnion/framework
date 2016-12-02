<?php
namespace Onion\Framework\Middleware\Internal;

use Interop\Http\Middleware\ServerMiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Request\Uri;

class ModulePathStripperMiddleware implements ServerMiddlewareInterface
{
    private $routePathPrefix;

    public function __construct($prefix)
    {
        $this->routePathPrefix = $prefix;
    }
    
    public function process(Request $request, DelegateInterface $delegate): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        return $delegate->process(
            $request->withUri(
                $request->getUri()->withPath(
                    '/' . ltrim(substr($path, strlen($this->routePathPrefix)). '/')
                )
            )
        );
    }
}
