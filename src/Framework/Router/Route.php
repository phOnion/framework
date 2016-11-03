<?php
/**
 * PHP Version 5.6.0
 *
 * @category Routing
 * @package  Onion\Framework\Router
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Router;

use Onion\Framework\Interfaces;

class Route implements Interfaces\Router\RouteInterface
{
    protected $name;
    protected $supportedMethods = [];
    protected $middleware = [];
    protected $path;
    protected $params = [];

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setMethods(array $methods)
    {
        $this->supportedMethods = array_map('strtoupper', $methods);

        return $this;
    }

    public function setMiddleware(array $callable)
    {
        $this->middleware = $callable;

        return $this;
    }


    public function setPattern($pattern)
    {
        $this->path = $pattern;

        return $this;
    }

    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPattern()
    {
        return $this->path;
    }


    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function getParams()
    {
        return $this->params;
    }

    /**
     * {@inheritDoc}
     */
    public function __clone()
    {
        $this->middleware = [];
        $this->name = null;
        $this->params = [];
        $this->path = null;
        $this->supportedMethods = [];
    }

    public function serialize()
    {
        return serialize([
            'handler' => $this->getMiddleware(),
            'name' => $this->getName(),
            'pattern' => $this->getPattern(),
            'methods' => $this->getSupportedMethods()
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->setMiddleware($data['handler'] ?: [])
            ->setPattern($data['pattern'])
            ->setName($data['name'])
            ->setSupportedMethods($data['methods']);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'pattern' => $this->getPattern(),
            'handler' => $this->getMiddleware(),
            'methods' => $this->getSupportedMethods()
        ];
    }

    /**
     * Defines the methods which are supported by the current
     * route object. Each entry in the `$methods` parameters
     * must be a valid "method", that the handler associated
     * is capable of understanding/handling.
     *
     * @param array $methods List of strings each identifying a
     * method (uppercase)
     * @return Interfaces\Router\RouteInterface
     */
    public function setSupportedMethods(array $methods = [])
    {
        array_walk($methods, function (&$method) {
            $method = strtoupper($method);
        });

        $this->supportedMethods = $methods;

        return $this;
    }

    /**
     * @return array List of methods supported by the route
     */
    public function getSupportedMethods()
    {
        return $this->supportedMethods;
    }
}
