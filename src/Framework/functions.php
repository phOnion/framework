<?php declare(strict_types=1);
namespace Onion\Framework;

use Onion\Framework\Dependency\Container;
use Psr\Container\ContainerInterface;

function compileConfigFiles(string $configDir, string $env): array
{
    if (!is_dir($configDir)) {
        throw new \RuntimeException(
            "Configuration directory '{$configDir}' does not exist"
        );
    }

    /** @var \DirectoryIterator $directoryIterator */
    $directoryIterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($configDir)
    );
    $configs = [];

    foreach ($directoryIterator as $file) {
        if ($directoryIterator->isDir() || $directoryIterator->isDot()) {
            continue;
        }
        $prefix = $file->getBasename(".global.php");
        if (!stripos($prefix, '.php') !== false) {
            $configs[$prefix] = include $file->getRealPath();
        }

        $prefix = $file->getBasename(".{$env}.php");
        if (!stripos($prefix, '.php') !== false) {
            if (!isset($configs)) {
                $configs[$prefix] = include $file->getRealPath();
            }

            $configs = merge($configs, [
                $prefix =>  include $file->getRealPath()
            ]);
        }
    }

    return $configs;
}

function merge(array $array1, array $array2): array
{
    foreach ($array2 as $key => $value) {
        if (array_key_exists($key, $array1)) {
            if (is_int($key)) {
                $array1[] = $value;
            } elseif (is_array($value) && is_array($array1[$key])) {
                $array1[$key] = array_merge_distinct($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        } else {
            $array1[$key] = $value;
        }
    }
    return $array1;
}
