<?php

class AddCourseConfigForMediadownload extends Migration
{
    public function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_MEDIADOWNLOAD_PER_COURSE',
            'section'     => 'opencast',
            'description' => 'Wird Nutzern angeboten, Aufzeichnungen herunterzuladen?',
            'range'       => 'course',
            'type'        => 'string',
            'value'       => 'inherit'
        ]);

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_MEDIADOWNLOAD_PER_COURSE'");
    }
}
