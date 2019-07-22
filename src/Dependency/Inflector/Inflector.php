<?php
namespace Onion\Framework\Dependency\Inflector;

class Inflector
{
    private $target;
    private $calls = [];

    public function __construct(string $target)
    {
        $this->target = $target;
    }

    public function call(string $method, ...$params): self
    {
        if (!isset($this->calls[$method])) {
            $this->calls[$method] = new Invocation($method);
        }

        $this->calls[$method]->with(...$params);

        return $this;
    }

    public function getMethods(): iterable
    {
        return $this->calls;
    }
}
