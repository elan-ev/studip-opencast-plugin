<?php
class AddConfigForSupportMail extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_SUPPORT_EMAIL',
            'section'     => 'opencast',
            'description' => 'Support Email-Adresse für Video-Feedback.',
            'range'       => 'global',
            'type'        => 'string',
            'value'       => $GLOBALS['UNI_CONTACT']
        ]);

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_SUPPORT_EMAIL'");
    }

}
