<?php
/**
 * PHP Version 5.6.0
 *
 * @category REST
 * @package  Onion\Framework\Hal
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Hal;

use Psr\Http\Message\UriInterface;

class Link
{
    protected $rel;
    protected $attributes = [];
    protected $type;

    public function __construct($rel, UriInterface $href, array $attributes = [])
    {
        $this->rel = $rel;
        $this->attributes = $attributes;
        $this->attributes['href'] = (string) $href;
    }

    public function getRel()
    {
        return $this->rel;
    }

    public function getHref()
    {
        return $this->attributes['href'];
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function hasType()
    {
        return array_key_exists('type', $this->attributes);
    }
}
