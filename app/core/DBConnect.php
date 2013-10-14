<?php

class DBConnect
{
    protected static $_instance = null;

    public static function getPDO()
    {
        if (self::$_instance === null) {

            try {
                self::$_instance = new PDO('mysql: host=' . Config::$dbhost . ';dbname=' . Config::$dbname, Config::$dbuser, Config::$dbpassword);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }

        return self::$_instance;
    }

    public static function returnNumQuery($sql, array $params)
    {
        $DBH = self::getPDO();
        try {
            $STH = $DBH->prepare($sql);
            if ($STH->execute($params))
                return $STH->rowCount();
            else
                return 0;
        } catch (PDOException $e) {
            echo($e->getMessage());
            return 0;
        }
    }

    public static function query($sql)
    {
        try {
            $DBH = self::getPDO();
            $DBH->query($sql);

        } catch (PDOException $e) {
            echo $e->getMessage();
        }
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