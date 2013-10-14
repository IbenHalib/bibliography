<?php

function autoload($class_name)
{
    foreach (Config::$class_folders as $folder)
        if (file_exists($folder . $class_name . '.php'))
            include $folder . $class_name . '.php';
}

function getControllerAction()
{

    //explode path and routing query to Controller and Action.
    if (isset($_SERVER['PATH_INFO']) && ($path = explode('/', $_SERVER['PATH_INFO'])) &&
        (isset($path[1]) && isset($path[2]))) {

        $arr['controller'] = $path[1] . 'Controller';
        $arr['action'] = $path[2] . 'Action';

        $arr['controller'][0] = strtoupper($arr['controller'][0]);

    } else {
        $arr['controller'] = Config::$default_controller;
        $arr['action'] = Config::$default_action;
    }

    return $arr;
}

spl_autoload_register('autoload');