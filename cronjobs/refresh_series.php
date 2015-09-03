<?php
/**
 * refresh_series.php
 *
 * @author André Klaßen <klassen@elan-ev.de>
 * @access public
 */


require_once 'lib/classes/CronJob.class.php';
require_once __DIR__ .'/../models/OCCourseModel.class.php';

class RefreshSeries extends CronJob {

    public static function getName()
    {
        return _('Opencast - "Serien-Refresh"');
    }
    public static function getDescription()
    {
        return _('Aktualisiert die Episodenüberischt aller in Stud.IP verbundenen Serien, die aufgezeichnet werden.');
    }
    public function execute($last_result, $parameters = array())
    {

        $stmt = DBManager::get()->prepare("SELECT DISTINCT `seminar_id` FROM `oc_seminar_series` WHERE 1");
        $stmt->execute(array());
        $courses  = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if(!empty($courses)){
            foreach($courses as $course_id) {
                $ocmodel = new OCCourseModel($course_id);
                $ocmodel->getEpisodes(true);
                unset($ocmodel);
            }
        }


        return true;
    }

}