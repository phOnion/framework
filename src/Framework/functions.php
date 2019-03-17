<?php declare(strict_types=1);
namespace Onion\Framework;

use function Onion\Framework\Common\merge as newMerge;

if (!function_exists('Onion\Framework\merge')) {
    function merge(array $array1, array $array2): array
    {
        trigger_error(__FUNCTION__ . " is deprecated, switch to '\Onion\Framework\Common\merge'", E_USER_DEPRECATED);

        return newMerge($array1, $array2);
    }
}
