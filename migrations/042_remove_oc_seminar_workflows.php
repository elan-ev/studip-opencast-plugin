<?php
class RemoveOcSeminarWorkflows extends Migration
{

    function up()
    {
        DBManager::get()->query("DROP TABLE IF EXISTS `oc_seminar_workflows`;");
    }

    function down() {
        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_seminar_workflows` (
            `config_id` INT NOT NULL DEFAULT 1,
            `workflow_id` varchar(255) NOT NULL,
            `seminar_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            `user_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            `mkdate` INT DEFAULT 0,
            PRIMARY KEY (`workflow_id`)
            ) ROW_FORMAT=DYNAMIC;");
    }
}
