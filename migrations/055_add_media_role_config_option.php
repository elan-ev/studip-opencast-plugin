<?php
class AddMediaRoleConfigOption extends Migration
{
    public function description()
    {
        return 'Add config to switch to the media role mode';
    }

    public function up()
    {
        $db = DBManager::get();

        $results = $db->query("SELECT * FROM oc_config ORDER BY id DESC");
        $config_id = $results->fetchColumn();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_MEDIA_ROLES',
            'section'     => 'opencast',
            'description' => 'Sollen die Rollen "Medienadmin" und "Medientutor" der Rollenverwaltung genutzt werden um Zugriffsrechte auf Videos zu definieren?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => false
        ]);


        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {

        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_MEDIA_ROLES'");

        SimpleOrMap::expireTableScheme();
    }
}