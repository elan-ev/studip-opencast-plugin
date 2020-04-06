<?php
class AddConfigForStudio extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_STUDIO',
            'section'     => 'opencast',
            'description' => 'Wird Nutzern angeboten, Aufzeichnungen mit Opencast Studio zu erstellen?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_STUDIO'");
    }

}
