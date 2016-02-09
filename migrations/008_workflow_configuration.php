<?php

class WorkflowConfiguration extends Migration {
    function up() {

        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_seminar_workflow_configuration` (
              `seminar_id` varchar(32) NOT NULL,
              `workflow_id` varchar(255) NOT NULL,
              `target` ENUM('schedule', 'upload') NOT NULL DEFAULT 'schedule',
              `mkdate` INT DEFAULT 0,
              `chdate` INT DEFAULT 0,
              PRIMARY KEY (`seminar_id`, `target`)
              );");
    }

    function down() {
        DBManager::get()->query("DROP TABLE IF EXISTS `oc_seminar_workflow_configuration`;");
    }

}