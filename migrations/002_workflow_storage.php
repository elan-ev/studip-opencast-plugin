<?php

class WorkflowStorage extends Migration {
    function up() {
        //seminar_id, workflow_id, user_id
                   
        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_seminar_workflows` (
              `workflow_id` varchar(255) NOT NULL,
              `seminar_id` varchar(32) NOT NULL,
              `user_id` varchar(32) NOT NULL,
              PRIMARY KEY (`workflow_id`)
              ) ENGINE=MyISAM;");
    }
    
    function down() {
        DBManager::get()->query("DROP TABLE IF EXISTS `oc_seminar_workflows`;");
    }
    
}