<?php
class UpdateTutorConf extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('REPLACE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_TUTOR_EPISODE_PERM',
            'section'     => 'opencast',
            'description' => 'Sollen Tutor/innen im Opencast-Plugin die gleichen Rechte haben wie Dozent/innen?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => false
        ]);

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
    }
}
