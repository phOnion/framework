<?php declare(strict_types=1);
namespace Onion\Framework\Router\Parsers;

use Onion\Framework\Router\Interfaces\ParserInterface;

/**
 * Class Regex
 *
 * @package Onion\Framework\Router\Parsers
 */
class Regex implements ParserInterface
{
    /**
     * @param string $path
     * @return string
     */
    public function parse(string $path): string
    {
        $path = str_replace(
            [':*', ':?', '*'],
            [':\w+', ':\w', '(?:.*)?'],
            $path
        );

        return preg_replace('~\{\{(.*)\}\}~uU', '[$1]', $this->convertOptionalGroupsToNonCapturable(
            $this->convertToCaptureBoundGroups($path)
        ));
    }

    /**
     * Make sure that conditionals surround the parameters
     * are not capturable, since they are a meta data and
     * there is no specific need to capture the conditional
     * group, but only the parameters.
     *
     * @param string $string
     * @return string
     */
    protected function convertOptionalGroupsToNonCapturable(string $string): string
    {
        return preg_replace(
            '~\[(?:/)?([^\[\]]+|(?R))\]~uU',
            '(?:$1)?',
            $string
        );
    }

    /**
     * Perofrm widening of capture groups, since \w+ will capture
     * only what the RegEx engine sees as a word character,
     * which is not necessarily correct in some cases, since some
     * special characters can also appear but they will not be
     * considered as well as unicode characters so it translates
     * \w+ to many \p{} flags.
     *
     * @param string $string
     * @return string
     */
    protected function convertToCaptureBoundGroups(string $string): string
    {
        return preg_replace(
            ['~\[(\w+)\]+~iuU', '~\[(\w+)\:(.*)\]+~iuU'],
            ['(?P<$1>{{\p{L}\p{C}\p{N}\p{Pd}\p{Ps}\p{Pe}\p{Pi}\p{Pf}\p{Pc}\p{S}%*,;&\'}}+)', '(?P<$1>$2)'],
            $string
        );
    }

    /**
     * @param string $pattern
     * @param string $uri
     * @return array
     */
    public function match(string $pattern, string $uri): array
    {
        $matches = [];
        $path = $uri;

        $result = preg_match($pattern, $path, $matches);

        if ($result > 0) {
            return $matches;
        }

        return [false];
    }
}
