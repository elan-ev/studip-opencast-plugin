<?php

class RemoveSubtitlesWorkflow extends Migration
{
    public function description()
    {
        return 'Remove "editor" from list of configurable workflows';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_workflow_config`
            MODIFY `used_for` enum('schedule','upload','studio','delete') CHARACTER SET latin1 COLLATE latin1_bin
        ");

        $db->exec("DELETE FROM `oc_workflow_config` WHERE `used_for` NOT IN ('schedule','upload','studio','delete')");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
    }
}