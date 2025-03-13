<?php

class RemoveWorker extends Migration
{
    public function description()
    {
        return 'Remove cronjob worker since it is not needed anymore';
    }

    public function up()
    {
        $scheduler = CronjobScheduler::getInstance();

        $cronjob_file = 'public/plugins_packages/elan-ev/OpencastV3/cronjobs/opencast_worker.php';

        // remove worker cronjob
        if ($task_id = CronjobTask::findByFilename($cronjob_file)[0]->task_id) {
            $scheduler->cancelByTask($task_id);
        }
    }

    public function down()
    {
    }
}