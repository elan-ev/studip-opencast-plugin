<?php

class AddOptionsSharingWorkspace extends Migration
{
    public function description()
    {
        return 'Add options for public sharing and student workspace upload';
    }

    public function up()
    {
        $db = DBManager::get();

        // Add global config to edit clear interval of videos in recycle bin
        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
            VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');

       $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_PUBLIC_SHARING',
            'section'     => 'opencast',
            'description' => 'Sollen Nutzende Videos weltweit öffentlich freigeben können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);

        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_STUDENT_WORKSPACE_UPLOAD',
            'section'     => 'opencast',
            'description' => 'Sollen Studierende Videos in ihrem Arbeitsplatz hochladen können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);
    }

    public function down()
    {
        $db = DBManager::get();

        // Remove global config
        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_PUBLIC_SHARING'");
        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_STUDENT_WORKSPACE_UPLOAD'");
    }
}