<?php

class FixCronjobs extends Migration
{
    const FILENAMES = [
        'public/plugins_packages/elan-ev/OpenCast/cronjobs/refresh_scheduled_events.php',
        'public/plugins_packages/elan-ev/OpenCast/cronjobs/refresh_series.php'
    ];

    public function description()
    {
        return 'adds a cronjob for reuploading filed media uploads and fixes all cronjob registrations';
    }

    public function up()
    {
        foreach (self::FILENAMES as $filename) {

            if (!$task_id = CronjobTask::findByFilename($filename)[0]->task_id) {
                $task_id = CronjobScheduler::registerTask($filename, true);
            }

            // Schedule job to run every 60 minutes
            if ($task_id) {
                CronjobScheduler::cancelByTask($task_id);
                CronjobScheduler::schedulePeriodic($task_id, -60);  // negative value means "every x minutes"
                CronjobSchedule::findByTask_id($task_id)[0]->activate();
            }
        }
    }

    function down() {}
}
