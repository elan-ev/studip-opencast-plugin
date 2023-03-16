<?php

class AddStudentUploadConfig extends Migration
{
    public function description()
    {
        return 'Add config for enabling upload from students';
    }

    public function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_STUDENT_UPLOAD',
            'section'     => 'opencast',
            'description' => 'Studierenden erlauben, Videos im Kurs hochzuladen?',
            'range'       => 'course',
            'type'        => 'boolean',
            'value'       => false
        ]);

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_STUDENT_UPLOAD'");
        $db->exec("DELETE FROM config_values WHERE field = 'OPENCAST_ALLOW_STUDENT_UPLOAD'");

        SimpleOrMap::expireTableScheme();
    }
}
