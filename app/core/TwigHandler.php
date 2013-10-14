<?php

class TwigHandler
{
    protected static $_instance = null;

    public static function getTwig()
    {
        if (self::$_instance === null) {
            $loader = new Twig_Loader_Filesystem(Config::$view_folder);
            self::$_instance = new Twig_Environment($loader, array(
                'cache' => Config::$twig_cache_folder,
                'debug' => Config::$debug_env,
            ));
        }

        return self::$_instance;
    }

    public static function render($content, array $data = Array()) {
        header('Content-Type: text/html; charset=utf-8');
        return self::getTwig()->render($content, $data);
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __destruct()
    {
        self::$_instance = null;
    }
}