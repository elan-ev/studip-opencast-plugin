<?php

class AddEpisodeVisibilityConfig extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_HIDE_EPISODES',
            'section'     => 'opencast',
            'description' => 'Sollen Videos standardmäßig nur für Lehrende sichtbar sein?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => false
        ]);

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        //remove config
        DBManager::get()->query("DELETE FROM config WHERE field = 'OPENCAST_HIDE_EPISODES'");
    }

}
