<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Onion\Framework\Http\Response
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Middleware;

use Onion\Framework\Http\Response\RawResponse;
use Onion\Framework\Interfaces\Hal\StrategyInterface;
use Onion\Framework\Interfaces\Middleware\FrameInterface;
use Onion\Framework\Interfaces\Middleware\MiddlewareInterface;
use Psr\Http\Message;
use Zend\Diactoros\Response\EmptyResponse;

class HalResponseMiddleware implements MiddlewareInterface
{
    /**
     * @var \Onion\Framework\Interfaces\Hal\StrategyInterface[]
     */
    protected $strategies;

    /**
     * HalResponseStrategy constructor.
     *
     * @param StrategyInterface[] $strategies
     */
    public function __construct(array $strategies = [])
    {
        $this->strategies = $strategies;
    }

    /**
     * @param Message\RequestInterface $request
     * @param FrameInterface           $frame
     *
     * @return Message\ResponseInterface
     */
    public function process(Message\RequestInterface $request, FrameInterface $frame = null)
    {
        $response = $frame->next($request);

        if ($response instanceof RawResponse) {
            $negotiatedResponse = new EmptyResponse(406);
            if (($ext = pathinfo($request->getUri()->getPath(), PATHINFO_EXTENSION)) !== null) {
                $negotiatedResponse = $this->negotiateByFileExtension($ext, $response);
            }

            if ($negotiatedResponse->getStatusCode() === 406 && $request->hasHeader('accept')) {
                $negotiatedResponse = $this->negotiateByAcceptHeader($request->getHeaderLine('accept'), $response);
            }

            return $negotiatedResponse;
        }


        return $response;
    }

    protected function negotiateByAcceptHeader($headerLine, Message\ResponseInterface $response)
    {
        /**
         * @var $response RawResponse
         */
        $acceptParts = explode(',', $headerLine);
        array_walk($acceptParts, function (&$value) {
            list($value, ) = explode(';', $value);
        });


        foreach ($this->strategies as $strategy) {
            if (array_intersect($acceptParts, $strategy->getSupportedTypes()) !== []) {
                return $strategy->process($response);
            }
        }

        return new EmptyResponse(406);
    }

    protected function negotiateByFileExtension($fileExtension, Message\ResponseInterface $response)
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->getSupportedExtension() === $fileExtension) {
                return $strategy->process($response);
            }
        }

        return new EmptyResponse(406);
    }
}
