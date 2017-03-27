<?php declare(strict_types=1);
namespace Onion\Framework\Router\Parsers;

use Onion\Framework\Router\Interfaces\ParserInterface;

class Regex implements ParserInterface
{
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

    protected function convertOptionalGroupsToNonCapturable(string $string): string
    {
        return preg_replace(
            '~\[(?:/)?([^\[\]]+|(?R))\]~uU',
            '(?:$1)?',
            $string
        );
    }

    protected function convertToCaptureBoundGroups(string $string): string
    {
        return preg_replace(
            ['~\[(\w+)\]+~iuU', '~\[(\w+)\:(.*)\]+~iuU'],
            ['(?P<$1>{{\p{L}\p{C}\p{N}\p{Pd}\p{Ps}\p{Pe}\p{Pi}\p{Pf}\p{Pc}\p{S}%*,;&\'}}+)', '(?P<$1>$2)'],
            $string
        );
    }

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
