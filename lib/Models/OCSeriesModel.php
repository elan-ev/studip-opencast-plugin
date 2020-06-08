<?php

namespace Opencast\Models;

use Opencast\Models\OCConfig;
use Opencast\Models\OCSeminarSeries;
use Opencast\LTI\OpencastLTI;
use \DBManager;

class OCSeriesModel
{

    // saves all series for later requests
    static private $allSeries = null;


    /**
     * [getSeriesFromOpencast description]
     *
     * @param  [type] $series    [description]
     *
     * @return [type]            [description]
     */
    static function getSeriesFromOpencast($series)
    {
        $sclient = SeriesClient::create($series['seminar_id']);
        if ($oc_series = $sclient->getSeries($series['series_id'])) {
            return self::transformSeriesJSON($oc_series);
        }

        return false;
    }

    static function getSeminarAndSeriesData()
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM oc_seminar_series WHERE schedule = '1';");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * [getSeriesForUser description]
     *
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     */
    static function getSeriesForUser($user_id)
    {
        if ($GLOBALS['perm']->have_perm('root', $user_id)) {
            $stmt = DBManager::get()->prepare("SELECT DISTINCT se.seminar_id, se.config_id, series_id FROM seminar_user AS su
                JOIN oc_seminar_series AS se ON (su.Seminar_id = se.seminar_id)
                JOIN oc_seminar_episodes AS ep ON (se.series_id = ep.series_id)
                WHERE ep.visible != 'invisible'");
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = DBManager::get()->prepare("SELECT DISTINCT se.seminar_id, se.config_id, series_id FROM seminar_user AS su
                JOIN oc_seminar_series AS se ON (su.Seminar_id = se.seminar_id)
                JOIN oc_seminar_episodes AS ep ON (se.series_id = ep.series_id)
                WHERE su.user_id = ?
                    AND ep.visible != 'invisible'");
            $stmt->execute([$user_id]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * transforms multidimensional series array into 2 dimensional array
     *
     * @param array $data
     * @return array
     */
    static private function transformSeriesJSON($data)
    {
        if (empty($data)) {
            return false;
        }

        $res = array();
        $var_name = 'http://purl.org/dc/terms/';

        foreach (get_object_vars($data->$var_name) as $key => $val) {
            $res[$key] = $val[0]->value;
        }

        return $res;
    }

    /**
     * write series for course in db
     *
     * @param string $course_id
     * @param string $series_id
     * @param string $visibility
     * @param int $schedule
     * @patam int mkdate
     *
     * @return type
     */
    static function setSeriesforCourse($course_id, $config_id, $series_id, $visibility = 'visible', $schedule = 0, $mkdate = 0)
    {
        self::removeSeriesforCourse($course_id);

        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_seminar_series (config_id, series_id, seminar_id, visibility, schedule, mkdate)
                VALUES (?, ?, ?, ?, ?, ? )");
        $stmt->execute(array($config_id, $series_id, $course_id, $visibility, $schedule, $mkdate));

        OpencastLTI::setAcls($course_id);
    }

    /**
     * delete series connection to course
     *
     * @param string $course_id
     * @param string $series_id
     * @return bool
     */
    static function removeSeriesforCourse($course_id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM
            oc_seminar_series
            WHERE seminar_id = ?");

        return $stmt->execute(array($course_id));
    }

    /**
     * return array with connected series dublin core xml
     *
     * @param string $courseID
     * @return array
     */
    static function getSeriesDCs($course_id)
    {
        $series = OCSeminarSeries::getSeries($course_id);
        $ret = array();

        foreach ($series as $ser) {
            if ($xml = SeriesClient::create($course_id)
                ->getXML('/' . $ser['series_id'] . '.xml')
            ) {
                $ret[] = $xml;
            }
        }

        return $ret;
    }

    /**
     * generate xml ACl string
     * $data = array('MATTERHORN_ROLE' => array('permission' => 'value'))
     * matterhorn role is the role defined in matterhorn
     * permission is the action to allow or forbid
     * value is bool for allow or forbid
     *
     * @param array $data
     * @return bool
     */
    static function createSeriesACL($data)
    {
        $content = array();

        foreach ($data as $role => $perm) {
            foreach ($perm as $action => $val) {
                $content[] = '<ace>'
                        . '<role>' . $role . '</role>'
                        . '<action>' . $action . '</action>'
                        . '<allow>' . $val . '</allow>'
                        . '</ace>';
            }
        }

        $str = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<acl xmlns="http://org.opencastproject.security">'
                . implode('', $content)
                . '</acl>';

        return $str;
    }

    /**
     * createSeriesDC - creates an xml representation for a new OC-Series
     *
     * @param string $course_id
     * @return string xml - the xml representation of the string
     */
    static function createSeriesDC($course_id)
    {
        $course       = new Seminar($course_id);
        $name         = $course->getName() . ' - ' . $course->getStartSemesterName();
        $license      = "&copy; " . gmdate(Y) . " " . $GLOBALS['UNI_NAME_CLEAN'];
        $rightsHolder = $GLOBALS['UNI_NAME_CLEAN'];
        $inst         = Institute::find($course->institut_id);

        $publisher    = $inst->name;
        $start        = $course->getStartSemester();
        $end          = $course->getEndSemesterVorlesEnde();
        $audience     = "General Public";
        $instructors  = $course->getMembers('dozent');
        $instructor   = array_shift($instructors);
        $contributor  = $GLOBALS['UNI_NAME_CLEAN'];
        $creator      = $instructor['fullname'];
        $language     = 'de';

        if (mb_strlen($course->description) > 1000) {
            $description .= mb_substr($course->description, 0, 1000);
            $description .= "... ";
        } else {
            $description = $course->description;
        }

        $data = [
            'title' => $name,
            'creator' => $creator,
            'contributor' => $contributor,
            'subject' => $course->form,
            'language' => $language,
            'license' => $license,
            'description' => $description,
            'publisher' => $publisher
        ];


        $content = [];

        foreach ($data as $key => $val) {
            $content[] = '<dcterms:' . $key . '><![CDATA[' . $val . ']]></dcterms:' . $key . '>';
        }

        $str = '<?xml version="1.0" encoding="UTF-8"?>'
                . '<dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" '
                . 'xmlns:dcterms="http://purl.org/dc/terms/" xmlns:oc="http://www.opencastproject.org/matterhorn/">'
                . implode('', $content)
                . '</dublincore>';

        return $str;
    }

    /**
     * getScheduledEpisodes - returns all scheduled episodes for a given course
     */

    static function getScheduledEpisodes($course_id)
    {
        $stmt = DBManager::get()->prepare("SELECT  * FROM
                oc_scheduled_recordings WHERE seminar_id = ?");
        $stmt->execute(array($course_id));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static function getCachedSeriesData($series_id)
    {
        $stmt = DBManager::get()->prepare("SELECT `content`
            FROM oc_series_cache WHERE `series_id` = ?");
        $stmt->execute(array($series_id));

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($result)) {
            return false;
        } else {
            foreach($result as $c) {
                $content = unserialize($c['content']);
            }

            if (is_null($content) || empty($content)) {
                return 'empty';
            } else {
                return $content;
            }
        }
    }

    static function setCachedSeriesData($series_id, $data)
    {
        $stmt = DBManager::get()->prepare("INSERT INTO
                oc_series_cache (`series_id`, `content`, `mkdate`, `chdate`)
                VALUES (?, ?, ?, ?)");

        return $stmt->execute(array($series_id, $data, time() ,time()));
    }

    static function updateCachedSeriesData($series_id, $data)
    {
        $stmt = DBManager::get()->prepare("UPDATE oc_series_cache
            SET `content` = ?, `chdate`= ?
            WHERE `series_id` = ?");

        return $stmt->execute(array($data, time(), $series_id));
    }

    static function clearCachedSeriesData()
    {
        DBManager::get()->exec("TRUNCATE oc_series_cache");
    }

    static function updateVisibility($seminar_id, $visibility)
    {
        $stmt = DBManager::get()->prepare("UPDATE
                oc_seminar_series SET `visibility` = ?  WHERE `seminar_id` = ?");

        return $stmt->execute(array($visibility, $seminar_id));
    }

    static function getVisibility($seminar_id)
    {
        $stmt = DBManager::get()->prepare("SELECT `visibility` FROM
                oc_seminar_series WHERE seminar_id = ?");
        $stmt->execute(array($seminar_id));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static function updateSchedule($seminar_id, $schedule)
    {
        $stmt = DBManager::get()->prepare("UPDATE
                oc_seminar_series SET `schedule` = ?  WHERE `seminar_id` = ?");

        return $stmt->execute(array($schedule, $seminar_id));
    }

    static function getWorkflowForEvent($seminar_id, $termin_id )
    {
        $stmt = DBManager::get()->prepare('SELECT `workflow_id`FROM `oc_scheduled_recordings`
                            WHERE seminar_id = ? AND `date_id` = ?');
        $stmt->execute(array($seminar_id, $termin_id));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static function getCoursesForSeries($series_id){
        $stmt = DBManager::get()->prepare("SELECT seminar_id FROM oc_seminar_series WHERE series_id = ?;");
        $stmt->execute([$series_id]);

        return $stmt->fetchAll();
    }
}
