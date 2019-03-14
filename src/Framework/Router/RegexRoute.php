<?php
declare(strict_types=1);
namespace Onion\Framework\Router;

class RegexRoute extends Route
{
    /** @var string[] $parameters */
    private $parameters = [];

    public function getParameters(): iterable
    {
        return array_filter($this->parameters, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    public function isMatch(string $path): bool
    {
        if (preg_match("~^{$this->parse($this->getPattern())}$~x", $path, $this->parameters)) {
            return true;
        }

        return false;
    }
}
