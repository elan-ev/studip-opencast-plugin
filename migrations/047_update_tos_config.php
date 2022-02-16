<?php
class UpdateTosConfig extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $results = $db->query("SELECT * FROM config_values WHERE field = 'OPENCAST_TOS'")->fetch();

        if (json_decode($results['value']) == false) {
            $lang = reset(array_keys($GLOBALS['CONTENT_LANGUAGES']));
            $new_value = json_encode([
                $lang => $results['value']
            ]);

            $stmt = $db->prepare("UPDATE config_values SET value = ? WHERE field = 'OPENCAST_TOS'");
            $stmt->execute([$new_value]);
        }

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {

    }

}
