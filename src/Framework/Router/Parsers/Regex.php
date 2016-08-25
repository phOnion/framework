<?php
/**
 * PHP Version 5.6.0
 *
 * @category Routing
 * @package  Onion\Framework\Router\Parsers
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Router\Parsers;

use Onion\Framework\Interfaces;

class Regex implements Interfaces\Router\ParserInterface
{
    public function parse($path)
    {
        $path = str_replace(
            [':*', ':?', '*'],
            [':\w+', ':\w', '(?:(\w*))'],
            $path
        );

        return $this->convertOptionalGroupsToNonCapturable(
            $this->convertToCaptureBoundGroups(
                $this->bootstrapParamsWithoutExplicitRegex($path)
            )
        );
    }

    public function bootstrapParamsWithoutExplicitRegex($str)
    {
        return /*preg_replace('#\[(\w+)\]#i', '[\w+]', $str);*/ $str;
    }

    protected function convertToCaptureBoundGroups($string)
    {
        $string = preg_replace(
            ['~\[(\w+)\]+~iuU', '~\[(\w+)\:(.*)\]+~iuU'],
            ['(?P<$1>[\p{L}\p{C}\p{N}\p{Pd}\p{Ps}\p{Pe}\p{Pi}\p{Pf}\p{Pc}\p{S}%*,;&\']+)', '(?P<$1>$2)'],
            $string
        );
        return $string;
    }

    protected function convertOptionalGroupsToNonCapturable($string)
    {
        return preg_replace(
            '~/\[([^\[\]]+|(?R))\]~uU',
            '/(?:$1)?',
            $string
        );
    }

    public function match($pattern, $uri)
    {
        $matches = [];
        $path = $uri;

        $result = preg_match($pattern, $path, $matches);

        if ($result > 0) {
            return $matches;
        }


        return false;
    }
}
