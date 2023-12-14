<?php

use Opencast\Models\Workflow;

class AddWorkflowSettings extends Migration
{
    public function description()
    {
        return 'Add settings column to table workflow';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_workflow` 
            ADD COLUMN `settings` TEXT NOT NULL DEFAULT '[]'
        ");

        SimpleOrMap::expireTableScheme();

        // Set default upload file types
        $upload_workflows = Workflow::findBySQL("tag = 'upload'");

        foreach ($upload_workflows as $upload_wf) {
            $upload_wf->settings = [
                'upload_file_types' => Workflow::DEFAULT_UPLOAD_FILE_TYPES,
             ];
            $upload_wf->store();
        }
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_workflow` DROP COLUMN `settings`");

        SimpleOrMap::expireTableScheme();
    }
}
