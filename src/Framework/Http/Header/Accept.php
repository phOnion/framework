<?php
declare(strict_types=1);
namespace Onion\Framework\Http\Header;

class Accept implements Interfaces\Header
{
    private $types = [];
    
    public function __construct(string $headerValue)
    {
        $contentTypes=explode(',', $headerValue);

        foreach ($contentTypes as $pair) {
            if (preg_match('~^(?P<type>[a-z0-9+-/.*]+)(?:;q=(?P<priority>[0-9.]{1,3}))?$~i', trim($pair), $matches)) {
                $this->types[strtolower(trim($matches['type']))] = (float) (isset($matches['priority']) ? trim($matches['priority']) : 1);
            }
        }
    }

    public function supports(string $contentType): bool
    {
        return isset($this->types[strtolower($contentType)]);
    }

    public function getPriority(string $contentType): float
    {
        return $this->supports($contentType) ?
            $this->types[strtolower($contentType)] : -1.0;
    }
}
