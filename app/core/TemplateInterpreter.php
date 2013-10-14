<?php

class TemplateInterpreter
{

    /**
     * @param $file_name
     * @return string
     */
    protected function openTemplate($id)
    {
//        $file_name = Config::$templates_folder . $file_name;
//
//        if (file_exists($file_name))
//            $content = file_get_contents($file_name);
//        else
//            return false;

        $DBH = DBConnect::getPDO();
        $STH = $DBH->prepare('SELECT db_schema, view_template FROM templates WHERE id=?');
        $STH->execute(array($id));

        $content = $STH->fetch(PDO::FETCH_NUM);

        return $content;
    }


    /**
     * @param $content
     * @return array|bool
     */
    protected function parseContent($content)
    {
        try {
            $content[0] = str_replace('<template>', '', $content[0]);
            $content[0] = str_replace('</template>', '', $content[0]);
            return $content;

        } catch (Extension $ex) {
            return false;
        }
    }
}
