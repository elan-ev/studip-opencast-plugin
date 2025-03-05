<?php
class AddAclCronjob extends Migration
{
    const FILENAME = 'public/plugins_packages/elan-ev/OpencastV3/cronjobs/opencast_sync_acls.php';

    function description()
    {
        return 'add acl cronjob';
    }

    function up()
    {
        $scheduler = CronjobScheduler::getInstance();

        // add new scheduling cronjob
        $task_id = $scheduler->registerTask(self::FILENAME, true);

        // Schedule job to run every 2 hours
        if ($task_id) {
            $scheduler->schedulePeriodic($task_id, 0, -2);  // negative value means "every x hours"
        }
    }

    function down()
    {
        // There is no going back from this migration!!!
    }

}

