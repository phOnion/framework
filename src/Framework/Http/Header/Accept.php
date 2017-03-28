<?php declare(strict_types=1);
namespace Onion\Framework\Http\Header;

class Accept implements Interfaces\AcceptInterface
{
    private $types = [];

    /**
     * Accept constructor.
     *
     * @param string $headerValue result of RequestInterface::getHeaderLine
     */
    public function __construct(string $headerValue)
    {
        $contentTypes=explode(',', $headerValue);

        foreach ($contentTypes as $pair) {
            if (preg_match(
                '~^(?P<type>[a-z0-9+-/.*]+)(?:[a-z0-9=\-;]+)?(?:;q=(?P<priority>[0-9.]{1,3}))?(?:[a-z0-9=\-;]+)?$~i',
                trim($pair),
                $matches
            )) {
                $this->types[strtolower(trim($matches['type']))] =
                    (float) (isset($matches['priority']) ? trim($matches['priority']) : 1);
            }
        }
    }

    /**
     * Check whether or not the current content-type
     * is supported by the client. Not that this
     * checks as-is and does not try to determine
     * if `application/*` is supported for example
     *
     * @param string $contentType
     * @return bool
     */
    public function supports(string $contentType): bool
    {
        return isset($this->types[strtolower($contentType)]);
    }

    /**
     * Retrieves the 'weight' of the $contentType provided.
     *
     * @param string $contentType
     * @return float
     */
    public function getPriority(string $contentType): float
    {
        return $this->supports($contentType) ?
            $this->types[strtolower($contentType)] : -1.0;
    }
}
