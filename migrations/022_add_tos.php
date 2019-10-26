<?php
class AddTos extends Migration
{

    function up()
    {
        $db = DBManager::get();

        DBManager::get()->query("INSERT INTO `oc_config_precise`(`name`, `description`, `value`, `for_config`) VALUES
            ('tos', 'Datenschutztext', '', -1)
        ");

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_SHOW_TOS',
            'section'     => 'opencast',
            'description' => 'Müssen Lehrende eine, Datenschutztext zustimmen, bevor sie das Opencast-Plugin in einer Veranstaltung verwenden können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => false
        ]);

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_tos` (
            `seminar_id` varchar(32) NOT NULL,
            `user_id` varchar(32) NOT NULL,
            PRIMARY KEY (`seminar_id`, `user_id`)
        )");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        //remove precise config
        DBManager::get()->query("DELETE FROM oc_config_precise WHERE name='tos'");
        DBManager::get()->query("DELETE FROM config WHERE field = 'OPENCAST_SHOW_TOS'");
        DBManager::get()->query('DROP TABLE IF EXISTS `oc_tos`');
    }

}
