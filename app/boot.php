<?php
require 'Config.php';
require 'functions/functions.php';

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

!isset($_SESSION['aut'])?$_SESSION['aut'] = false:0;
!isset($_SESSION['login'])?$_SESSION['login'] = '':0;
!isset($_SESSION['isadmin'])?$_SESSION['isadmin'] = false:0;
!isset($_SESSION['id'])?$_SESSION['user_id'] = false:0;

Twig_Autoloader::register();

$load = getControllerAction();

$class = new $load['controller'];

$class->$load['action']();