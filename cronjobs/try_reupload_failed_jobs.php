<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (13:37)
 */

require_once 'lib/classes/CronJob.class.php';


class TryReuploadFailedJobs extends CronJob {

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