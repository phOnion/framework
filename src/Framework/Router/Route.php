<?php declare(strict_types=1);
namespace Onion\Framework\Router;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Onion\Framework\Hydrator\MethodHydrator;
use Onion\Framework\Router\Interfaces\RouteInterface;

/**
 * Class Route
 *
 * @package Onion\Framework\Router
 * @codeCoverageIgnore
 */
class Route implements RouteInterface
{
    use MethodHydrator;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $methods = [];

    /**
     * @var string
     */
    private $pattern;

    /**
     * @var DelegateInterface
     */
    private $delegate;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?? spl_object_hash($this);
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return DelegateInterface
     */
    public function getDelegate(): DelegateInterface
    {
        return $this->delegate;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param mixed $methods
     */
    public function setMethods(array $methods)
    {
        array_walk($methods, function (&$value) {
            $value = strtoupper($value);
        });

        $this->methods = $methods;
    }

    /**
     * @param mixed $pattern
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * @param mixed $delegate
     */
    public function setDelegate($delegate)
    {
        $this->delegate = $delegate;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }
}
