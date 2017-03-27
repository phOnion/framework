<?php declare(strict_types=1);
namespace Onion\Framework\Application;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Onion\Framework\Application\Interfaces\ApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmitterInterface;

/**
 * Class Application
 *
 * @package Onion\Framework\Application
 */
class Application implements ApplicationInterface
{
    /**
     * @var DelegateInterface
     */
    protected $delegate;

    /**
     * @var EmitterInterface
     */
    protected $emitter;

    /**
     * Application constructor.
     *
     * @param DelegateInterface $delegate
     * @param EmitterInterface $emitter
     */
    public function __construct(DelegateInterface $delegate, EmitterInterface $emitter)
    {
        $this->delegate = $delegate;
        $this->emitter = $emitter;
    }

    public function run(ServerRequestInterface $request)
    {
        return $this->emitter->emit($this->process($request));
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws \Throwable Rethrows the exceptions if no $delegate is available
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request): ResponseInterface
    {
        return $this->delegate->process($request);
    }
}
