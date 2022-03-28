<?php

declare(strict_types=1);

namespace Onion\Framework\Http\Header;

class Accept implements Interfaces\AcceptInterface
{
    /** @var string $name */
    private $name;
    /** @var string $value */
    private $value;
    /** @var float[] $types */
    private $types = [];

    /**
     * Accept constructor.
     *
     * @param string $headerValue result of RequestInterface::getHeaderLine
     */
    public function __construct(string $name, string $headerValue)
    {
        $this->name = $name;
        $this->value = $headerValue;

        $contentTypes = explode(',', $headerValue);

        foreach ($contentTypes as $pair) {
            if (
                preg_match(
                    '~^(?P<type>[a-z0-9+-/.*]+)(?:[a-z0-9=\-;]+)?' .
                        '(?:;q=(?P<priority>[0-9.]{1,3}))?(?:[a-z0-9=\-;]+)?$~i',
                    trim($pair),
                    $matches
                )
            ) {
                $this->types[$matches['type']] =
                    (float) (isset($matches['priority']) ? $matches['priority'] : 1);
            }
        }
    }

    public function getName(): string
    {
        return ucwords($this->name, '-');
    }

    public function getRawValue(): string
    {
        return $this->value;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Check whether or not the current content-type
     * is supported by the client. Not that this
     * checks as-is and does not try to determine
     * if `application/*` is supported for example
     */
    public function supports(string $value): bool
    {
        foreach (array_keys($this->types) as $pattern) {
            $pattern = str_replace(['.', '*', '/', '+'], ['\.', '(.*)', '\/', '\+'], $pattern);
            if (preg_match("#^$pattern$#i", $value) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieves the 'weight' of the $contentType provided.
     */
    public function getPriority(string $value): float
    {
        foreach ($this->types as $pattern => $weight) {
            $pattern = str_replace(['*', '.', '/', '+'], ['(.*)', '.', '\/', '\+'], $pattern);
            if (preg_match("#^$pattern$#i", $value) > 0) {
                return $weight;
            }
        }

        return -1.0;
    }

    public function __toString(): string
    {
        return $this->getRawValue();
    }
}
