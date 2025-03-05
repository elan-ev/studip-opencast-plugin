<?php
class ConfigOptionPermissionAssignment extends Migration
{


    public function description()
    {
        return 'Add config option to allow permission assignment';
    }

    public function up()
    {
        $db = DBManager::get();

        // Add global config to allow permission assignment
        $stmt = $db->prepare('REPLACE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
            VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');

        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_PERMISSION_ASSIGNMENT',
            'section'     => 'opencast',
            'description' => 'Sollen Nutzende Rechte an Videos vergeben können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);
    }

    public function down()
    {
        $db = DBManager::get();

        DBManager::get()->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_PERMISSION_ASSIGNMENT'");
        DBManager::get()->exec("DELETE FROM config_values WHERE field = 'OPENCAST_ALLOW_PERMISSION_ASSIGNMENT'");
    }
}
