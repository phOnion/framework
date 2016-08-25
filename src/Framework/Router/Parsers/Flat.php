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

class Flat implements Interfaces\Router\ParserInterface
{
    public function parse($path)
    {
        $components = parse_url((string) $path);

        if (!$components || !array_key_exists('path', $components)) {
            throw new \InvalidArgumentException(sprintf(
                'It appears that path is malformed and `parse_url` cannot retrieve a valid path. Received "%s"',
                $path
            ));
        }

        return (array_key_exists('host', $components) ? $components['host'] : '') .
                $components['path'];
    }

    public function match($pattern, $path)
    {
        if ($pattern !== $path) {
            throw new \InvalidArgumentException('No match');
        }
    }
}
