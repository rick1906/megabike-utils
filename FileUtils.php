<?php

namespace megabike\utils;

abstract class FileUtils
{

    public static function makeDirRecursive($dir, $permissions = 0777)
    {
        $pdir = dirname($dir);
        if ($pdir && $pdir !== '/' && !is_dir($pdir)) {
            $r = self::makeDirRecursive($pdir);
            if (!$r) {
                return false;
            }
        }

        if (@mkdir($dir, $permissions)) {
            @chmod($dir, $permissions);
        } else {
            return is_dir($dir); // fix for parallel dir creation
        }
        return true;
    }

    public static function getRelativePath($path, $rootPath)
    {
        $rp = rtrim(self::normalizePath($rootPath), '/').'/';
        $p = rtrim(self::normalizePath($path), '/').'/';

        $len = strlen($rp);
        if (!strncmp($p, $rp, $len)) {
            return rtrim(substr($p, $len), '/');
        }
        return false;
    }

    public static function getAbsolutePath($path, $rootPath)
    {
        if (self::isAbsolutePath($path)) {
            return $path;
        } else {
            return rtrim($rootPath, '/\\').'/'.$path;
        }
    }

    public static function normalizePath($path)
    {
        $npath = preg_replace('/\/+/', '/', str_replace('\\', '/', $path));
        $segments = explode('/', $npath);
        $parts = array();
        $part = '';
        foreach ($segments as $segment) {
            if ($segment === '.') {
                continue;
            }
            $part = end($parts);
            if ($segment === '..') {
                if ($part === '') {
                    continue; // in case "/../bla"
                } elseif ($part === '..' || $part === false) {
                    $part[] = $segment; // in case "../../"
                } else {
                    array_pop($parts);
                }
            } else {
                $parts[] = $segment;
            }
        }
        return implode('/', $parts);
    }

    public static function isAbsolutePath($path)
    {
        return !empty($path) && ($path[0] === '/' || $path[0] === '\\' || (strlen($path) < 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/')));
    }

}
