<?php

namespace megabike\utils;

class ClassExractor
{

    public static function getClassesFromFile($file)
    {
        return (new ClassExractor($file))->getClasses();
    }

    public static function getClassFromFile($file, $preferredClass = null, $strict = false)
    {
        return (new ClassExractor($file))->getClass($preferredClass, $strict);
    }

    protected $file;
    protected $content;
    protected $tokens;

    public function __construct($file)
    {
        $this->file = $file;
        $this->parseFile($file);
    }

    protected function parseFile($file)
    {
        if (!is_file($file)) {
            $this->content = false;
            $this->tokens = false;
        } else {
            $this->content = file_get_contents($file);
            if ($this->content) {
                $this->tokens = token_get_all($this->content);
            } else {
                $this->tokens = false;
            }
        }
    }

    public function getClasses()
    {
        return $this->tokens ? $this->getClassesInternal($this->tokens) : array();
    }

    public function getClass($preferredClass = null, $strict = false)
    {
        $classes = $this->getClasses();
        if (!$classes) {
            return null;
        }
        if ($preferredClass === null) {
            return $classes[0];
        }

        $cn0 = ltrim($preferredClass, '\\');
        $cn1 = $this->getClassName($cn0);
        foreach ($classes as $class) {
            if (!strcasecmp($class, $cn0)) {
                return $class;
            }
            if (!$strict && !strcasecmp($this->getClassName($class), $cn1)) {
                return $class;
            }
        }
        return null;
    }

    protected function getClassesInternal($parsed)
    {
        $classes = array();
        $namespace = null;

        foreach ($parsed as $ix => $token) {
            $ts = $this->getFullToken($token);
            if ($ts[0] === T_NAMESPACE) {
                $name = $this->getNextString($parsed, $ix + 1, array(T_STRING, T_NS_SEPARATOR));
                if ($name !== null) {
                    $namespace = ltrim($name, '\\');
                } else {
                    $namespace = null;
                }
            }
            if ($ts[0] === T_CLASS || $ts[0] === T_INTERFACE || defined('T_TRAIT') && $ts[0] === T_TRAIT) {
                $name = $this->getNextString($parsed, $ix + 1);
                if ($name !== null) {
                    if ($namespace !== null) {
                        $classes[] = $namespace.'\\'.$name;
                    } else {
                        $classes[] = $name;
                    }
                }
            }
        }

        return $classes;
    }

    protected function getNextString($parsed, $index, $validTokens = null)
    {
        $found = false;
        $string = '';
        if ($validTokens === null) {
            $validTokens = array(T_STRING);
        }
        for ($i = $index; $i < count($parsed); ++$i) {
            $ts = $this->getFullToken($parsed[$i]);
            if (!$found && ($ts[0] === T_WHITESPACE || $ts[0] === T_COMMENT || $ts[0] === T_DOC_COMMENT)) {
                continue;
            }
            if (in_array($ts[0], $validTokens, true)) {
                $found = true;
                $string .= $ts[1];
                continue;
            }
            break;
        }
        return $found ? $string : null;
    }

    protected function getFullToken($token)
    {
        if (is_array($token)) {
            return $token;
        } else {
            return array($token, $token);
        }
    }

    protected function getClassName($class)
    {
        $p = strrpos($class, '\\');
        if ($p !== false) {
            return substr($class, $p + 1);
        }
        return $class;
    }

}
