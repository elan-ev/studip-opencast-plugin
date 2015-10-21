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

        $semester_cur = Semester::findCurrent();


        $stmt = DBManager::get()->prepare("SELECT ocs.seminar_id FROM oc_seminar_series AS ocs
                LEFT JOIN seminare AS sem ON ocs.seminar_id = sem.Seminar_id WHERE start_time <= IFNULL(?, UNIX_TIMESTAMP())
                          AND (? <= start_time + duration_time OR duration_time = -1) AND ocs.schedule=1");
        $stmt->execute(array($semester_cur->beginn, $semester_cur->beginn));
        $courses  = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if(!empty($courses)){
            foreach($courses as $course_id) {
                $ocmodel = new OCCourseModel($course_id);
                $course = Course::find($course_id);
                echo sprintf(_("Aktualisieriere Episoden für den Kurs %s im Semester %s\n "), $course->name, $semester_cur->name);
                $ocmodel->getEpisodes(true);
                unset($ocmodel);

            }
        }

        return true;
    }

}