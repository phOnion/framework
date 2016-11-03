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

class Resource
{
    protected $data = [];
    protected $resources = [];
    protected $links = [];

    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    public function addLink(Link $link)
    {
        $this->links[$link->getRel()] = $link;

        return $this;
    }

    public function addCurie(Link $link)
    {
        $this->links['curies'][$link->getRel()] = $link;

        return $this;
    }

    public function addResource($rel, self $resource)
    {
        $this->resources[$rel][] = $resource;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Link[][]
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param $rel
     *
     * @return Link|bool The link if exists or false otherwise
     */
    public function getLink($rel)
    {
        if (!$this->hasLink($rel)) {
            return false;
        }

        return $this->links[$rel];
    }

    public function hasLink($rel)
    {
        return array_key_exists($rel, $this->links);
    }

    public function getResources()
    {
        return $this->resources;
    }
}
