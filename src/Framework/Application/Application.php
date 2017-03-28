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
     * @param DelegateInterface $delegate The delegate with the global middleware
     * @param EmitterInterface $emitter The emitter that processes and sends the response to the client
     */
    public function __construct(DelegateInterface $delegate, EmitterInterface $emitter)
    {
        $this->delegate = $delegate;
        $this->emitter = $emitter;
    }

    /**
     * "Run" the application. Triggers the delegate
     * provided and when a repsonse is returned it
     * passes it to the emitter for final processing
     * before sending it to the client
     *
     * @param ServerRequestInterface $request
     * @return null
     */
    public function run(ServerRequestInterface $request)
    {
        return $this->emitter->emit($this->process($request));
    }

    /**
     * Triggers processing of the provided delegate,
     * without emitting the response. Useful in the
     * context of the application running as a module
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request): ResponseInterface
    {
        return $this->delegate->process($request);
    }
}
