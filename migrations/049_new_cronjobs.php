<?php

class NewCronjobs extends Migration
{
    const BASE_DIR = 'public/plugins_packages/elan-ev/OpenCast/cronjobs/';

    public function description()
    {
        return 'Remove old cronjobs and add new video discovery and worker cronjob';
    }

    public function up()
    {
        $scheduler = CronjobScheduler::getInstance();

        foreach ([
            self::BASE_DIR . 'refresh_scheduled_events.php',
            self::BASE_DIR . 'refresh_series.php'
        ] as $filename) {
            if ($task_id = CronjobTask::findByFilename($filename)[0]->task_id) {
                $scheduler->cancelByTask($task_id);
                $scheduler->unregisterTask($task_id);
            }
        }

        // add video discovery cronjob
        if (!$task_id = CronjobTask::findByFilename(self::BASE_DIR . 'opencast_discover_videos.php')[0]->task_id) {
            $task_id =  $scheduler->registerTask(self::BASE_DIR . 'opencast_discover_videos.php', true);
        }

        // add the new cronjobs
        if ($task_id) {
            $scheduler->cancelByTask($task_id);
            $scheduler->schedulePeriodic($task_id, -10);  // negative value means "every x minutes"
            CronjobSchedule::findByTask_id($task_id)[0]->activate();
        }


        // add worker cronjob
        if (!$task_id = CronjobTask::findByFilename(self::BASE_DIR . 'opencast_worker.php')[0]->task_id) {
            $task_id =  $scheduler->registerTask(self::BASE_DIR . 'opencast_worker.php', true);
        }

        // add the new cronjobs
        if ($task_id) {
            $scheduler->cancelByTask($task_id);
            $scheduler->schedulePeriodic($task_id, -1);  // negative value means "every x minutes"
            CronjobSchedule::findByTask_id($task_id)[0]->activate();
        }
    }

    function down() {}
}
