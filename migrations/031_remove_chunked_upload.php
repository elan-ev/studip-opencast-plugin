<?php

class RemoveChunkedUpload extends Migration
{
    const FILENAMES = [
        'public/plugins_packages/elan-ev/OpencastV3/cronjobs/try_reupload_failed_jobs.php'
    ];

    public function description()
    {
        return 'remove cronjob for reuploading filed media uploads';
    }

    public function up()
    {
        foreach (self::FILENAMES as $filename) {
            if ($task_id = CronjobTask::findByFilename($filename)[0]->task_id) {
                $task_id = CronjobScheduler::getInstance()->unregisterTask($task_id);
            }
        }
    }

    function down() {}
}
