<?php
class AddLivestreamConfigSwitch extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $db->query("INSERT IGNORE INTO `oc_config_precise`
            (`name`, `description`, `value`, `for_config`) VALUES
            ('livestream', 'Soll das Live-Streaming aktiviert werden?', 1, -1)
        ");
    }
    
    function down()
    {
        $db = DBManager::get();

        $db->query("DELETE FROM oc_config_precise
            WHERE name = 'livestream'
        ");
    }
}
