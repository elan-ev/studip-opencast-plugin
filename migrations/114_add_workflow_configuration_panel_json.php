<?php

use Opencast\Models\Workflow;

class AddWorkflowConfigurationPanelJson extends Migration
{
    public function description()
    {
        return 'Add configuration panel (JSON) column to table workflow';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_workflow`
            ADD COLUMN `configuration_panel_json` TEXT NULL
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_workflow` DROP COLUMN `configuration_panel_json`");

        SimpleOrMap::expireTableScheme();
    }
}
