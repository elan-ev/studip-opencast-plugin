<?php
/**
 * try_reupload_failed_jobs.php - cronjob to retry failed upload jobs
 */

require_once __DIR__ . '/../bootstrap.php';


class TryReuploadFailedJobs extends CronJob
{

    public static function getName()
    {
        return _('Opencast - "Reupload"');
    }

    public static function getDescription()
    {
        return _('Opencast: Versucht gescheiterte Upload-Jobs nochmal zu wiederholen.');
    }

    public function execute($last_result, $parameters = [])
    {
        OCJobManager::try_reupload_old_jobs();

        return true;
    }

}
