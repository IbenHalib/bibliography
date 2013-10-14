<?php

/**
 * Class TableHandler
 */
class TableHandler extends TemplateInterpreter
{

    /**
     * @param $id
     */
    public function syncTemplateToTable($id)
    {
        $content = parent::openTemplate($id);
        if ($content) {
            $parsed_content = parent::parseContent($content);
            $table_schema = $this->parseTableSchema($parsed_content[0]);
            return $this->checkTable($table_schema, $id);
        }

        return false;
    }

    /**
     * @param $table_schema
     * @param $filename
     */
    protected function save_actual_schema($table_schema, $id)
    {
        $DBH = DBConnect::getPDO();
        $STH = $DBH->prepare('UPDATE '.Config::$db_tables['templates'].
        ' SET actual_schema=? WHERE id=?');
        $STH->execute(array(serialize($table_schema), $id));
    }


    /**
     * @param $filename
     * @return bool|mixed
     */
    protected function load_actual_schema($id)
    {

        $DBH = DBConnect::getPDO();
        $STH = $DBH->prepare('SELECT actual_schema FROM '.Config::$db_tables['templates'].' WHERE id=?');
        $STH->execute(array($id));
        $res = $STH->fetch();

        if (!$res)
            return false;

        $value = unserialize($res['actual_schema']);
        return $value;
    }


    /**
     * @param $xml_schema
     * @return array
     */
    protected function parseTableSchema($xml_schema)
    {
        try {
            if (strpos($xml_schema, '<') === false)
                die('0');

            $xmlObject = new SimpleXMLElement($xml_schema);
            if ($xmlObject->count() == 0)
                die('0');

        } catch (Exception $e) {
            die('0');
        }

        $reader = new XMLReader();
        $reader->XML($xml_schema);

        if (!$reader->read())
            die(0);

        if ($reader->name === 'table')
            $table = trim($reader->getAttribute('name'));

        $table = str_replace(array("'", "`", ';'), array('', '', ''), $table);

        //Getting table fields structure. Setting read values or defined if not defined in template
        while ($reader->read()) {
            if ($reader->name === 'field' && $reader->nodeType == XMLReader::ELEMENT) {

                $field = str_replace(array("'", "`", ';'), array('', '', ''), trim($reader->readString()));
                $fields[$field] = array(
                    'type' => !is_null($reader->getAttribute('type')) ? $reader->getAttribute('type') : Config::$default_db_schema['type'],
                    'isnull' => (!is_null($reader->getAttribute('isnull')) ? ($reader->getAttribute('isnull') === 'true') ? true : false : Config::$default_db_schema['isnull']) ? 'NULL' : 'NOT NULL',
                    'default' => is_null(!is_null($reader->getAttribute('default')) ? ($reader->getAttribute('default') === 'NULL') ? null : $reader->getAttribute('default') : Config::$default_db_schema['default']) ? 'NULL' : '\'' . $reader->getAttribute('default') . '\'',
                    'label' => !is_null($reader->getAttribute('label')) ? $reader->getAttribute('label') : Config::$default_db_schema['label'],

                    //Multifields and subfields parcing
                    'multifields' => !is_null($reader->getAttribute('multifields'))? $reader->getAttribute('multifields'): 1,
                    'subfields' => !is_null($reader->getAttribute('subfields'))? str_replace('\'', '"', $reader->getAttribute('subfields')): null
                );

                $fields[$field]['type'] = str_replace(array("'", "`", ';'), array('', '', ''), $fields[$field]['type']);
                $fields[$field]['isnull'] = str_replace(array("'", "`", ';'), array('', '', ''), $fields[$field]['isnull']);
                $fields[$field]['default'] = str_replace(array("'", "`", ';'), array('', '', ''), $fields[$field]['default']);
                $fields[$field]['label'] = str_replace(array("'", "`", ';'), array('', '', ''), $fields[$field]['label']);

            }
        }

        return array('table' => $table, 'fields' => $fields);
    }


    /**
     * @param array $table_schema
     * @param $filename
     */
    protected function checkTable(array $table_schema, $id)
    {
        $table_name = $table_schema['table'];

        if (!$this->isTableExists($table_name)) {

            $this->createTable($table_schema);

        } else {

            $diff = $this->searchChanges($table_schema, $id);
            $this->alterTable($table_name, $diff);

        }

        $this->save_actual_schema($table_schema, $id);

        $this->checkLabels($table_schema);

        return $table_name;
    }


    /**
     * @param array $table_schema
     * @return bool
     */
    protected function createTable(array $table_schema)
    {

        $table_name = $table_schema['table'];
        $fields = $table_schema['fields'];

        if (!(strpos($table_name, '`') === false))
            return false;

        //Begin create query
        $sql = 'CREATE TABLE `' . $table_name . '` (';

        $sql .= implode(', ', Config::$default_db_fields). ', ';

        print_r($fields);
        foreach ($fields as $key => $field) {

            if ((!(strpos($key, '`') === false)) ||
                (!(strpos($field['type'], '`') === false)) ||
                (!(strpos($field['isnull'], '`') === false)) ||
                (!(strpos($field['default'], '`') === false))
            )
                return false;

            $sql .= '`' . $key . '` ' .
                $field['type'] . ' ' .
                $field['isnull'] . ' ' .
                'DEFAULT ' . $field['default'] . ',';

            if ($field['subfields'] !== null) {
                $sql .= '`' . $key . '_vis` ' .
                    $field['type'] . ' ' .
                    $field['isnull'] . ' ' .
                    'DEFAULT ' . $field['default'] . ',';
            }
        }

        $sql .= ' PRIMARY KEY (`id`) ) ENGINE=' . Config::$db_engine . ' DEFAULT CHARSET=utf8';
        //end of query

        DBConnect::query($sql);

        return true;
    }


    /**
     * @param $table_name
     * @param $diff
     */
    protected function alterTable($table_name, $diff)
    {
        if (count($diff['create']) > 0) {

            $sql = 'ALTER TABLE `' . $table_name . '` ';

            foreach ($diff['create'] as $key => $field) {
                $sql .= 'ADD COLUMN `';
                $sql .= $key . '` ' . $field['type'] . ' ' . $field['isnull'] . ' DEFAULT ' . $field['default'] . ',';
            }

            $sql = substr($sql, 0, strlen($sql) - 1);
            DBConnect::query($sql);

        }

        if (count($diff['delete']) > 0) {

            $sql = 'ALTER TABLE `' . $table_name . '` ';

            foreach ($diff['delete'] as $key => $field) {
                $sql .= 'DROP COLUMN `';
                $sql .= $key . '`, ';
            }

            $sql = substr($sql, 0, strlen($sql) - 2);
            DBConnect::query($sql);

            //Clear labels
            $DBH = DBConnect::getPDO();
            $STH = $DBH->prepare('DELETE FROM '.Config::$db_tables['column_properties'].' WHERE table_name=?');
            $STH->execute(array($table_name));
        }


    }

    /**
     * @param $table
     * @return int
     */
    protected function isTableExists($table)
    {
        return DBConnect::returnNumQuery('SHOW TABLES LIKE ?', array($table));
    }


    /**
     * @param $table_schema
     * @param $id
     * @return mixed
     */
    protected function searchChanges($table_schema, $id)
    {
        $table_schema_saved = $this->load_actual_schema($id);

        $diff['create'] = array_diff_key($table_schema['fields'], $table_schema_saved['fields']);
        $diff['delete'] = array_diff_key($table_schema_saved['fields'], $table_schema['fields']);

        return $diff;
    }

    //Updating labels for fields: If exist - update, else - insert.
    //ONLY LABELS CAN BE MODIFIED IN EXISTING TEMLATES. IN OTHER CASES FIELDS ONLY CAN BE ADDED OR REMOVED
    protected function checkLabels(array $table_schema)
    {
        $table_name = $table_schema['table'];
        $fields = $table_schema['fields'];

        $DBH = DBConnect::getPDO();

        $DBH->beginTransaction();

        $STH0 = $DBH->prepare('SELECT id FROM '.Config::$db_tables['column_properties'].
        ' WHERE table_name=? AND column_name=?');
        $STH = $DBH->prepare('UPDATE '.Config::$db_tables['column_properties'].
        ' SET label=?, subfields=?, multifields=? WHERE table_name=? AND column_name=?');
        $STH1 = $DBH->prepare('INSERT INTO '.Config::$db_tables['column_properties'].
        '(label, table_name, column_name, subfields, multifields) VALUES(?, ?, ?, ?, ?)');

        foreach ($fields as $key => $field) {

            //  Searching if label exist
            $STH0->execute(array($table_name, $key));

            if ($STH0->rowCount() > 0)
                $STH->execute(array($field['label'], $field['subfields'], $field['multifields'], $table_name, $key));
            else {
                $STH1->execute(array($field['label'], $table_name, $key, $field['subfields'], $field['multifields']));
            }
        }

        $DBH->commit();
    }

}