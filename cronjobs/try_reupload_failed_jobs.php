<?php
/**
 * try_reupload_failed_jobs.php - cronjob to retry failed upload jobs
 */

require_once 'lib/classes/CronJob.class.php';

require_once $this->trails_root . '/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root . '/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root . '/classes/OCRestClient/IngestClient.php';
require_once $this->trails_root . '/classes/OCRestClient/UploadClient.php';
require_once $this->trails_root . '/classes/OCRestClient/ArchiveClient.php';
require_once $this->trails_root . '/models/OCModel.php';
require_once $this->trails_root . '/models/OCSeriesModel.php';
require_once $this->trails_root . '/models/OCCourseModel.class.php';
require_once $this->trails_root . '/models/OCEndpointModel.php';

require_once $this->trails_root . '/classes/OCJsonFile.php';

require_once $this->trails_root . '/classes/OCJobManager.php';
require_once $this->trails_root . '/classes/OCJob.php';
require_once $this->trails_root . '/classes/OCJobLocation.php';


class TryReuploadFailedJobs extends CronJob
{

    public static function getName()
    {
        return _('Opencast - "Reupload"');
    }

    public static function getDescription()
    {
        return _('Versucht gescheiterte Upload-Jobs nochmal zu wiederholen.');
    }

    public function execute($last_result, $parameters = array())
    {
        OCJobManager::try_reupload_old_jobs();
        return true;
    }

}
