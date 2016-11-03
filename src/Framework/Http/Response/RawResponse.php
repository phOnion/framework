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
namespace Onion\Framework\Http\Response;

use Zend\Diactoros\Response;

class RawResponse extends Response
{
    public function __construct($body, $status = 200, array $headers = [])
    {
        parent::__construct('php://memory', $status, $headers);
        $this->getBody()->write(serialize($body));
    }
}
