<?php
class UpdateTos extends Migration
{

    public function description()
    {
        return 'Move TOS text from oc_config to global config';
    }

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');

        $tos = $db->query('SELECT tos FROM oc_config WHERE id = 1')->fetchColumn();

        $stmt->execute([
            'name'        => 'OPENCAST_TOS',
            'section'     => 'opencast',
            'description' => 'Terms of service',
            'range'       => 'global',
            'type'        => 'string',
            'value'       => $tos
        ]);

        try {
            $db->exec('ALTER TABLE oc_config DROP tos');
        } catch (PDOException $e) {}

        $db->exec("UPDATE config
            SET description = 'Müssen Lehrende einem Datenschutztext zustimmen, bevor sie das Opencast-Plugin in einer Veranstaltung verwenden können?'
            WHERE field = 'OPENCAST_SHOW_TOS'
        ");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_TOS'");

        $db->exec("ALTER TABLE oc_config ADD tos TEXT NULL");
    }

}
