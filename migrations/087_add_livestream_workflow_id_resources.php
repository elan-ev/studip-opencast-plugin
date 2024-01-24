<?php

class AddLivestreamWorkflowIdResources extends Migration
{
    public function description()
    {
        return 'Add livestream workflow id to resources table';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_resources` ADD COLUMN
            `livestream_workflow_id` varchar(64) DEFAULT NULL');

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_resources`
            DROP COLUMN `livestream_workflow_id`');

        SimpleOrMap::expireTableScheme();
    }
}
