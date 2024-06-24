<?php
class AddDefaultConfigOption extends Migration
{
    public function description()
    {
        return 'Allows the setting of one Opencast server as default';
    }

    public function up()
    {
        $db = DBManager::get();

        $results = $db->query("SELECT * FROM oc_config ORDER BY id DESC");
        $config_id = $results->fetchColumn();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_DEFAULT_SERVER',
            'section'     => 'opencast',
            'description' => 'Das ist der standardmäßig verwendete Opencast-Server.',
            'range'       => 'global',
            'type'        => 'integer',
            'value'       => $config_id
        ]);

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
        VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_SCHEDULER',
            'section'     => 'opencast',
            'description' => 'Sollen Aufzeichnungen geplant werden können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {

        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_DEFAULT_SERVER'");
        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_SCHEDULER'");

        SimpleOrMap::expireTableScheme();
    }
}