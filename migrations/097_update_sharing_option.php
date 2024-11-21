<?php

class UpdateSharingOption extends Migration
{
    public function description()
    {
        return 'Rename option to include link sharing as well';
    }

    public function up()
    {
        $db = DBManager::get();

        // Add global config to edit clear interval of videos in recycle bin
        $stmt = $db->prepare('REPLACE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
            VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');

       $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_PUBLIC_SHARING',
            'section'     => 'opencast',
            'description' => 'Sollen Nutzende Videos teilen oder weltweit öffentlich freigeben können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);
    }

    public function down()
    {

    }
}