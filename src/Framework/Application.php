<?php
/**
 * PHP Version 5.6.0
 *
 * @category Kernel
 * @package  Onion\Framework
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework;

use Onion\Framework\Interfaces\Middleware\StackInterface;
use \Psr\Http\Message;

class Application
{
    /**
     * @var StackInterface
     */
    protected $stack;

    public function __construct(StackInterface $stack)
    {
        $this->stack = $stack;
    }

    public function run(Message\RequestInterface $request)
    {
        ob_start();
        $response = $this->stack->handle($request);
        foreach ($response->getHeaders() as $header => $headerLine) {
            /**
             * @var string[] $headerLine
             */
            $first = true;
            foreach ($headerLine as $value) {
                header(sprintf('%s: %s', ucwords($header, '-'), $value), $first, $response->getStatusCode());
                $first = false;
            }
        }
        ob_end_clean();
        echo $response->getBody();
    }
}
