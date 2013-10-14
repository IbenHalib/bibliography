<?php
/**
 * Class Config
Warning: Require writting permissions for $cache_folder
 */
class Config
{
    public static
    $admins = array('admin');

    public static
        $debug_env = true;

    public static
        $dbhost = 'localhost';

    public static
        $dbname = 'biblio';

    public static
        $dbuser = 'root';

    public static
        $dbpassword = '1111';

    public static
        $default_db_schema = array(
        'type' => 'text',
        'default' => null,
        'isnull' => true,
        'label' => 'Без мітки'
    );

    public static
        $default_db_fields = array(

        //Mandatory - will be set as PRIMARY KEY in every table.
        '`id` INT(11) NOT NULL AUTO_INCREMENT',

        //Every template require author
        '`id_author` INT(11) NOT NULL',

        //And creation date
        '`date` DATETIME NOT NULL'
    );

    public static
        $db_engine = 'InnoDB';

    public static
        $class_folders = array('app/core/', 'app/controllers/', 'app/view/');

    //Require writting permissions!!!
    public static
        $cache_folder = 'cache/';

    //Require writting permissions!!!
    public static
        $twig_cache_folder = 'cache/twig/';

    public static
        $current_scheme_folder = 'templates/current_db_state/';

    public static
        $view_folder = 'app/view/';

    public static
        $default_controller = 'IndexController';

    public static
        $default_action = 'contentAction';

    public static
        $db_tables = array(
        'templates' => 'templates',
        'users' => 'users',
        'column_properties' => 'column_properties'
    );
}