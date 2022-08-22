<?php

class SchedulingCronJob extends Migration
{
    const FILENAME = 'public/plugins_packages/elan-ev/OpenCast/cronjobs/opencast_refresh_scheduling.php';

    public function description()
    {
        return 'adds a cronjob for validating, updating and refreshing scheduled events and all related jobs.';
    }

    public function up()
    {
        $task_id = CronjobScheduler::registerTask(self::FILENAME, true);

        // Schedule job to run every 360 minutes
        if ($task_id) {
            CronjobScheduler::schedulePeriodic($task_id, -120);  // negative value means "every x minutes"
        }
    }

    function down() {
        if ($task_id = CronjobTask::findByFilename(self::FILENAME)->task_id) {
            CronjobScheduler::unregisterTask($task_id);
        }
    }
}
