<?php
declare(strict_types=1);
namespace Onion\Framework\Application;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmitterInterface;

class Application implements ServerMiddlewareInterface
{
    /**
     * @var DelegateInterface
     */
    protected $delegate;

    /**
     * @var EmitterInterface
     */
    protected $emitter;

    public function __construct(DelegateInterface $delegate, EmitterInterface $emitter)
    {
        $this->delegate = $delegate;
        $this->emitter = $emitter;
    }

    public function run(ServerRequestInterface $request)
    {
        $this->emitter->emit($this->process($request, null));
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @throws \Throwable Rethrows the exceptions if no $delegate is available
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate = null): ResponseInterface
    {
        try {
            return $this->delegate->process($request);
        } catch (\Throwable $ex) {
            if ($delegate === null) {
                throw $ex;
            }

            return $delegate->process($request->withAttribute('exception', $ex));
        }
    }
}
