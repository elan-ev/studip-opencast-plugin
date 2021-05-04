<?php

class AddLogTos extends Migration {

    function up()
    {
        $db = DBManager::get();
        $query = $db->prepare("INSERT INTO log_actions (action_id, name, description, info_template, active) VALUES (?, ?, ?, ?, ?)");

        $query->execute(array(md5('OC_TOS'), 'OC_TOS', 'Opencast: TOS akzeptiert/abgelehnt', '%user hat TOS %info in %sem(%affected)', 1));
    }

    function down()
    {
        $db = DBManager::get();
        $query = $db->prepare("DELETE FROM log_actions WHERE action_id = ?");
        $query2 = $db->prepare("DELETE FROM log_events WHERE action_id = ?");
        $query->execute(array(md5('OC_TOS')));
        $query2->execute(array(md5('OC_TOS')));
    }
}
