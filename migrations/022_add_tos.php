<?php
class AddTos extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_SHOW_TOS',
            'section'     => 'opencast',
            'description' => 'Müssen Lehrende einem Datenschutztext zustimmen, bevor sie das Opencast-Plugin in einer Veranstaltung verwenden können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => false
        ]);

        $db->exec("ALTER TABLE oc_config ADD tos TEXT NULL");

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_tos` (
            `seminar_id` varchar(32) NOT NULL,
            `user_id` varchar(32) NOT NULL,
            PRIMARY KEY (`seminar_id`, `user_id`)
        )");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_SHOW_TOS'");
        $db->exec('DROP TABLE IF EXISTS `oc_tos`');
        $db->exec('ALTER TABLE oc_config DROP IF EXISTS tos');
    }

}
