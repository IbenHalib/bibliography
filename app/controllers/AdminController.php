<?php

class AdminController
{
    public function __construct()
    {
        if (!$_SESSION['isadmin'] || !$_SESSION['aut']) {
            die('You don\'t have admin permissions');
        }
    }

    public function templatesAction()
    {
        $DBH = DBConnect::getPDO();
        $STH = $DBH->prepare('SELECT id, name, change_data FROM `'.Config::$db_tables['templates'].'`');
        $STH->execute();
        $templates = $STH->fetchAll();

        echo TwigHandler::render('templates.html.twig', array('templates' => $templates));
    }

    public function editTemplateAction()
    {
        if (isset($_GET['id'])) {
            settype($_GET['id'], 'int');
            if (!isset($_POST['submit'])) {

                $DBH = DBConnect::getPDO();
                $STH = $DBH->prepare('SELECT id, name, change_data, db_schema, view_template
                FROM '.Config::$db_tables['templates'].' WHERE id = ?');
                $STH->execute(array($_GET['id']));
                $template = $STH->fetch();

                echo TwigHandler::render('edit_template.html.twig', array('template' => $template, 'mode' => 'edit'));
            } else {
                $objDateTime = new DateTime('NOW');

                $DBH = DBConnect::getPDO();
                $STH = $DBH->prepare('UPDATE '.Config::$db_tables['templates'].'
                 SET name=?, db_schema=?, view_template=?, change_data=? WHERE id = ?');

                $res = (int)$STH->execute(array($_POST['name'],
                        $_POST['db_schema'],
                        $_POST['view_template'],
                        $objDateTime->format('c'),
                        $_GET['id'])
                );

                if ($res) {

                    //Try to create table
                    $table_generator = new TableHandler;
                    $table = $table_generator->syncTemplateToTable($_GET['id']);

                    //Save the name of created table
                    if ($table) {

                        $STH = $DBH->prepare('UPDATE '.Config::$db_tables['templates'].'
                         SET db_table_name=? WHERE id=?');
                        $STH->execute(array($table, $_GET['id']));

                    }
                }

                echo $res;
            }

        } else {
            echo('Require id');
        }
    }

    public function addTemplateAction()
    {
        if (!isset($_POST['submit']))
            echo TwigHandler::render('edit_template.html.twig', array('mode' => 'add'));
        else {
            $objDateTime = new DateTime('NOW');

            $DBH = DBConnect::getPDO();
            $STH = $DBH->prepare('INSERT INTO '.Config::$db_tables['templates'].'(name, db_schema, view_template, change_data) VALUES(?, ?, ?, ?)');
            $res = (int)$STH->execute(array($_POST['name'], $_POST['db_schema'], $_POST['view_template'], $objDateTime->format('c')));

            if ($res) {

                $table_generator = new TableHandler;

                $id = $DBH->lastInsertId();
                $table = $table_generator->syncTemplateToTable($id);

                $STH = $DBH->prepare('UPDATE '.Config::$db_tables['templates'].' SET db_table_name=? WHERE id=?');
                $STH->execute(array($table, $id));
            }

            echo $res;
        }

    }

    public function deleteTemplateAction() {

        if (isset($_GET['id'])) {
            settype($_GET['id'], 'int');

            $DBH = DBConnect::getPDO();

            //Selecting table, because removing caused
            //Templates have to be connected (<-->) with table: no template <--> no table
            $STH = $DBH->prepare('SELECT db_table_name FROM '.Config::$db_tables['templates'].' WHERE id = ?');
            $STH->execute(array($_GET['id']));
            $res = $STH->fetch();
            $table_name = $res['db_table_name'];

            $DBH->query('DROP TABLE IF EXISTS '. $table_name);

            $STH = $DBH->prepare('DELETE FROM '.Config::$db_tables['templates'].' WHERE id = ?');
            $STH->execute(array($_GET['id']));

            $STH = $DBH->prepare('DELETE FROM '.Config::$db_tables['column_properties'].
            ' WHERE table_name=?');
            $STH->execute(array($table_name));
        }
    }
}