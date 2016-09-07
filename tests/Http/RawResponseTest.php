<?php
/**
 * PHP Version 5.6.0
 *
 * @category Unknown Category
 * @package  Tests\Http
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */

namespace Tests\Http;


use Onion\Framework\Http\Response\RawResponse;

class RawResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testConversion()
    {
        $response = new RawResponse(new \stdClass());
        $this->assertSame(serialize(new \stdClass()), (string) $response->getBody());
    }
}
