<?php
class AddConfigForScheduler extends Migration
{
    public function description()
    {
        return 'Add config to show or hide the scheduler functionality';
    }

    public function up()
    {
        $db = DBManager::get();

        $results = $db->query("SELECT * FROM oc_config ORDER BY id DESC");
        $config_id = $results->fetchColumn();

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

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_SCHEDULER'");

        SimpleOrMap::expireTableScheme();
    }
}