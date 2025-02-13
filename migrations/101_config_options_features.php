<?php
class ConfigOptionsFeatures extends Migration
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
            'name'        => 'OPENCAST_ALLOW_SHARING',
            'section'     => 'opencast',
            'description' => 'Sollen Nutzende Videos teilen können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);

        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_TECHNICAL_FEEDBACK',
            'section'     => 'opencast',
            'description' => 'Sollen Nutzende Technisches Feedback zu einzelnen Videos geben können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);

        // Update info text for old config
        $stmt_update = $db->prepare('UPDATE config
            SET description = :description
            WHERE field = :name');

        $stmt_update->execute([
            'name'        => 'OPENCAST_ALLOW_PUBLIC_SHARING',
            'description' => 'Sollen Nutzende Videos weltweit öffentlich freigeben können?',
        ]);

        $stmt_update->execute([
            'name'        => 'OPENCAST_SUPPORT_EMAIL',
            'description' => 'Technisches Feedback an folgende Adresse schicken:',
        ]);
    }

    public function down()
    {

    }
}