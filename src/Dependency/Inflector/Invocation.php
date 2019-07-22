<?php
namespace Onion\Framework\Dependency\Inflector;

class Invocation
{
    private $method;
    private $params = [];

    public function __construct(string $method)
    {
        $this->method = $method;
    }

    public function with(...$params): self
    {
        $this->params[] = $params;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParameterSets(): iterable
    {
        return $this->params;
    }
}
