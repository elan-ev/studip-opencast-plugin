<?php

class CronjobRefreshScheduledEvents extends Migration
{
    // const FILENAME = 'public/plugins_packages/elan-ev/OpenCast/cronjobs/refresh_scheduled_events.php';
    public function description()
    {
        return 'adds a cronjob for refreshing scheduled events of an scheduled series in Stud.IP';
    }
    public function up()
    {
        return;
        $task_id = CronjobScheduler::registerTask(self::FILENAME, true);

        // Schedule job to run every 360 minutes
        if ($task_id) {
            CronjobScheduler::schedulePeriodic($task_id, -120);  // negative value means "every x minutes"
        }
    }
    function down()
    {
        return;
        if ($task_id = CronjobTask::findByFilename(self::FILENAME)->task_id) {
            CronjobScheduler::unregisterTask($task_id);
        }
    }
}
