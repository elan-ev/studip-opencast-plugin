<?php
class AddConfigForMediadownload extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_MEDIADOWNLOAD',
            'section'     => 'opencast',
            'description' => 'Wird Nutzern angeboten, Aufzeichnungen herunterzuladen?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => false 
        ]);

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_MEDIADOWNLOAD'");
    }

}
