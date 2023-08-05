<?php

class RemoveEditorWorkflowConfig extends Migration
{
    public function description()
    {
        return 'Remove "editor" from list of configurable workflows';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_workflow_config`
            MODIFY `used_for` enum('schedule','upload','studio','delete')
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
    }
}