<?php
class UpdateWorkflowTables extends Migration
{
    public function description()
    {
        return 'Change workflow tables to allow assigning of workflows to upload types';
    }

    public function up()
    {
        DBManager::get()->exec("ALTER TABLE `oc_workflow_config`
            ADD `used_for` enum('schedule','upload','studio') NOT NULL AFTER `workflow`;
        ");

        DBManager::get()->exec("DROP TABLE `oc_workflow_config_scope`");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {

        SimpleOrMap::expireTableScheme();
    }
}