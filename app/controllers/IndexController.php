<?php

class IndexController
{
    //Basic layout rendering
    public function contentAction()
    {
        echo TwigHandler::render('layout.html.twig', array('aut' => $_SESSION['aut'],
            'user' => $_SESSION['login'],
            'isadmin' => $_SESSION['isadmin']));
    }

    //Main menu (top) rendering
    public function menuAction()
    {
        echo TwigHandler::render('menu.html.twig', array('aut' => $_SESSION['aut'],
            'user' => $_SESSION['login'],
            'isadmin' => $_SESSION['isadmin']
        ));
    }

    //Search page
    public function searchAction()
    {
        echo TwigHandler::render('search.html.twig', array('aut' => $_SESSION['aut']));
    }

    public function dbsearchAction()
    {

    }

    public function loginAction()
    {
        $DBH = DBConnect::getPDO();
        $STH = $DBH->prepare('SELECT id, login FROM users WHERE login=? AND password=MD5(?)');

        if ($STH->execute(array($_POST['login'], $_POST['password']))) {

            $result = $STH->fetch();
            $_SESSION['aut'] = true;
            $_SESSION['login'] = $_POST['login'];
            $_SESSION['isadmin'] = in_array($_SESSION['login'], Config::$admins);
            $_SESSION['id'] = $result['id'];

            echo 1;
        } else {
            echo 0;
        }
    }

    public function exitAction()
    {
        $_SESSION['login'] = '';
        $_SESSION['aut'] = false;
        $_SESSION['isadmin'] = false;
        echo 1;
    }

    public function sourcesAction()
    {
        $DBH = DBConnect::getPDO();
        //$STH = $DBH->prepare('')

        echo TwigHandler::render('sources.html.twig');
    }

    public function addSourceAction()
    {
        $DBH = DBConnect::getPDO();

        if (!isset($_POST['submit'])) {
            //Getting type's list (book, journal, etc)
            $STH = $DBH->prepare('SELECT id, name FROM templates');
            $STH->execute();
            $source_types = $STH->fetchAll();

            echo TwigHandler::render('add_source.html.twig', array('types' => $source_types));
        } else {
            if (isset($_POST['source_type_select'])) {
                settype($_POST['source_type_select'], 'int');

                //Getting work db name (according to source type)
                $STH = $DBH->prepare('SELECT db_table_name FROM ' . Config::$db_tables['templates'] .
                ' WHERE id=?');
                $STH->execute(array($_POST['source_type_select']));
                $res = $STH->fetch();
                $table_name = $res['db_table_name'];

                //Getting updating fields list of selected table (according to user data)
                foreach ($_POST as $key => $field) {
                    $STH = $DBH->prepare('SELECT column_name FROM ' . Config::$db_tables['column_properties'] .
                    ' WHERE id=?');
                    $STH->execute(array($key));

                    if ($STH->rowCount()) {
                        $res = $STH->fetch();
                        $fields[0][] = $res['column_name'];
                        $fields[1][] = $field;
                    }
                }

                //adding author id
                $fields[0][] = 'id_author';
                $fields[1][] = $_SESSION['id'];

                $db_fields = '`' . implode('`, `', $fields[0]) . '`';

                $STH = $DBH->prepare('INSERT INTO ' . $table_name . '(' . $db_fields . ', `date`)' .
                'VALUES (' . implode(', ', array_fill(0, count($fields[0]), '?')) . ', NOW())');

                print_r($fields[1]);
                $STH->execute($fields[1]);

            }
        }
    }

    public function loadSourceFieldsAction()
    {
        $DBH = DBConnect::getPDO();

        //Setting default adding source (book, for example)
        if (!isset($_GET['id'])) {
            $STH = $DBH->prepare('SELECT MIN(id) FROM templates');
            $STH->execute();
            $res = $STH->fetch();
            $_GET['id'] = $res['MIN(id)'];
        } else {
            settype($_GET['id'], 'int');
        }

        //Getting the work table name
        $STH = $DBH->prepare('SELECT db_table_name FROM templates WHERE id=?');
        $STH->execute(array($_GET['id']));
        $res = $STH->fetch();
        $table_name = $res['db_table_name'];

        //Getting Rows list from table (according to db schema builds form)
        $STH = $DBH->prepare(
            'SELECT columns.column_name AS field,
            columns.column_type AS type,
			labels.label,
			labels.id,
			labels.subfields,
			labels.multifields
            FROM information_schema.columns columns
            JOIN ' . Config::$dbname . '.column_properties labels ON labels.table_name=columns.table_name
            AND labels.column_name=columns.column_name
            WHERE columns.table_name=\'' . $table_name . '\' AND columns.column_name<>\'id\'
            ORDER by columns.ordinal_position ASC');
        $STH->execute(array($table_name));

        $fields = $STH->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fields as $key => $field) {
            preg_match("/(\w*+)(\(\d*\))?+/", $field['type'], $type);
            $fields[$key]['type'] = $type[1];
            $fields[$key]['subfields'] = json_decode($fields[$key]['subfields'], true);
        }

        echo TwigHandler::render('source_fields.html.twig', array('fields' => $fields));
    }

}

