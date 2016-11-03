<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Onion\Framework\Interfaces\Hal
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Onion\Framework\Interfaces\Hal;

use Psr\Http\Message\ResponseInterface;

interface StrategyInterface
{
    /**
     * Return a list of the accepted content types that are
     * supported by the strategy
     * @return array
     */
    public function getSupportedTypes();

    /**
     * Return the file extensions that is supported by the
     * strategy.
     *
     * @return string
     */
    public function getSupportedExtension();

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function process(ResponseInterface $response);
}
