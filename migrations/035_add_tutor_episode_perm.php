<?php
class AddTutorEpisodePerm extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_TUTOR_EPISODE_PERM',
            'section'     => 'opencast',
            'description' => 'Sollen Tutoren Rechte für das Bearbeiten der Episoden haben?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => false
        ]);

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_TUTOR_EPISODE_PERM'");
    }

}
