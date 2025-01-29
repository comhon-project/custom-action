<?php

namespace Tests\Support;

class Utils
{
    public static function getBasePath(string $path = ''): string
    {
        return self::joinPaths(dirname(dirname(__DIR__)), $path);
    }

    public static function getTestPath(string $path = ''): string
    {
        return self::getBasePath(self::joinPaths('tests', $path));
    }

    public static function getAppPath(string $path = ''): string
    {
        return self::getBasePath(self::joinPaths('workbench', 'app', $path));
    }

    public static function joinPaths($basePath, ...$paths)
    {
        foreach ($paths as $index => $path) {
            if (empty($path)) {
                unset($paths[$index]);
            } else {
                $paths[$index] = DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
            }
        }

        return $basePath.implode('', $paths);
    }
}
