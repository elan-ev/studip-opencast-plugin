<?php
class AddAllowStudygroupConf extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_STUDYGROUP_CONF',
            'section'     => 'opencast',
            'description' => 'Sollen Nutzer das Plugin in Studiengruppen einschalten können?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_ALLOW_STUDYGROUP_CONF'");
    }

}