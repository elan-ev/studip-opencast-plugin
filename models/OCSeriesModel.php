<?php

use Opencast\Models\OCSeminarSeries;
use Opencast\Models\OCSeminarEpisodes;
use Opencast\LTI\OpencastLTI;

class OCSeriesModel
{

    /**
     * Retrieve series for seminar from Opencast
     *
     * @param  string $series_id  the series_id
     * @param  string $seminar_id the seminar the series is connected to
     *
     * @return mixed             the series from opencast or false
     */
    public static function getSeriesFromOpencast($series_id, $seminar_id)
    {
        $sclient = SeriesClient::create($seminar_id);
        if ($oc_series = $sclient->getSeries($series_id)) {
            return self::transformSeriesJSON($oc_series);
        }

        return false;
    }

    /**
     * List all series user has access to globally and in the passed context (if any)
     *
     * @param  string $user_id user to get series for
     * @param  string $context_id optional, the context to include
     *
     * @return array          an array if the type ['series_id' => 'seminar_id', ...]
     */
    public static function getSeriesForUser($user_id, $context_id = null)
    {
        $result = [];

        if ($GLOBALS['perm']->have_perm('admin', $user_id)) {
            // get all free videos,
            $stmt = DBManager::get()->prepare("SELECT DISTINCT se.series_id, se.seminar_id
                FROM oc_seminar_series AS se
                JOIN oc_seminar_episodes AS ep ON (
                    se.series_id = ep.series_id
                    AND ep.seminar_id = se.seminar_id
                )
                WHERE ep.visible = 'free'");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } else {
            // get all free videos user has access to
            $stmt = DBManager::get()->prepare("SELECT DISTINCT se.series_id, se.seminar_id
                FROM seminar_user AS su
                JOIN oc_seminar_series AS se ON (su.Seminar_id = se.seminar_id)
                JOIN oc_seminar_episodes AS ep ON (
                    se.series_id = ep.series_id
                    AND ep.seminar_id = se.seminar_id
                )
                WHERE su.user_id = ?
                    AND ep.visible = 'free'");

            $stmt->execute([$user_id]);
            $result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // get visible videos for current context
        if ($context_id) {
            $stmt = DBManager::get()->prepare("SELECT DISTINCT se.series_id, se.seminar_id
                FROM oc_seminar_series AS se
                JOIN oc_seminar_episodes AS ep ON (
                    se.series_id = ep.series_id
                    AND ep.seminar_id = se.seminar_id
                )
                WHERE se.seminar_id = :seminar_id
                    AND ep.visible != 'invisible'");

            $stmt->execute([':seminar_id' => $context_id]);

            $result = array_merge($result, $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
        }

        return $result;
    }

    /**
     * transforms multidimensional series array into 2 dimensional array
     *
     * @param array $data
     * @return array
     */
    private static function transformSeriesJSON($data)
    {
        if (empty($data)) {
            return false;
        }

        $res      = [];
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
     * @param int mkdate
     *
     * @return type
     */
    public static function setSeriesforCourse($course_id, $config_id, $series_id, $visibility = 'visible', $mkdate = 0)
    {
        self::removeSeriesforCourse($course_id);

        // Set Series
        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_seminar_series (config_id, series_id, seminar_id, visibility, mkdate)
                VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$config_id, $series_id, $course_id, $visibility, $mkdate]);

        // Set Episodes of that series too!
        // Getting it directly from opencast helps to get more controll!
        $events_api_client = \ApiEventsClient::create($course_id);
        $unstored_episodes = $events_api_client->getSeriesEpisodes($series_id);
        foreach ($unstored_episodes as $episode) {
            \OCModel::setEpisode($episode['identifier'], $series_id, $course_id, $visibility, 0);
        }

        OpencastLTI::setAcls($course_id);

        return (!empty($unstored_episodes)) ? count($unstored_episodes) : 0;
    }

    /**
     * delete series connection to course
     *
     * @param string $course_id
     * @return bool
     */
    public static function removeSeriesforCourse($course_id)
    {
        // Delete series from studip db
        $stmt = DBManager::get()->prepare("DELETE FROM
            oc_seminar_series
            WHERE seminar_id = ?");

        // Remove episode caches!
        $oc_seminar_episodes = OCSeminarEpisodes::findBySQL(
            'seminar_id = ?',
            [$course_id]
        );
        foreach ($oc_seminar_episodes as $episode) {
            $cache_key = 'sop/episodes/'. $episode->episode_id;
            StudipCacheFactory::getCache()->expire($cache_key);
        }

        // Delete stored episode in studip db
        $stmt_episodes = DBManager::get()->prepare("DELETE FROM
            oc_seminar_episodes
            WHERE seminar_id = ?");

        return $stmt->execute([$course_id]) && $stmt_episodes->execute([$course_id]);
    }

    /**
     * return array with connected series dublin core xml
     *
     * @param string $course_id
     * @return array
     */
    public static function getSeriesDCs($course_id)
    {
        $series = OCSeminarSeries::getSeries($course_id);
        $ret    = [];

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
    public static function createSeriesACL($data)
    {
        $content = [];

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
    public static function createSeriesDC($course_id)
    {
        $course       = new Seminar($course_id);
        $name         = $course->getName() . ' - ' . $course->getStartSemesterName();
        $license      = "&copy; " . gmdate('Y') . " " . $GLOBALS['UNI_NAME_CLEAN'];
        $inst         = Institute::find($course->institut_id);

        $publisher   = $inst->name;
        $instructors = $course->getMembers('dozent');
        $instructor  = array_shift($instructors);
        $contributor = $GLOBALS['UNI_NAME_CLEAN'];
        $creator     = $instructor['fullname'];
        $language    = 'de';

        $data = [
            'title'       => $name,
            'creator'     => $creator,
            'contributor' => $contributor,
            'subject'     => $course->form,
            'language'    => $language,
            'license'     => $license,
            'publisher'   => $publisher
        ];

        // create safe xml using XMLWriter
        $xw = new XMLWriter();
        $xw->openMemory();
        $xw->startDocument('1.0', 'UTF-8');
        $xw->startElement("dublincore");
        $xw->startAttribute('xmlns');
        $xw->text('http://www.opencastproject.org/xsd/1.0/dublincore/');
        $xw->endAttribute();

        $xw->startAttribute('xmlns:dcterms');
        $xw->text('http://purl.org/dc/terms/');
        $xw->endAttribute();

        $xw->startAttribute('xmlns:oc');
        $xw->text('http://www.opencastproject.org/matterhorn/');
        $xw->endAttribute();

        foreach ($data as $key => $val) {
            $xw->startElement('dcterms:' . $key);
            $xw->text($val);
            $xw->endElement();
        }

        $xw->endElement();
        $xw->endDocument();
        return $xw->outputMemory();
    }

    /**
     * getScheduledEpisodes - returns all scheduled episodes for a given course
     * @param $course_id
     * @return array
     */
    public  static function getScheduledEpisodes($course_id)
    {
        $stmt = DBManager::get()->prepare("SELECT  * FROM
                oc_scheduled_recordings WHERE seminar_id = ?");
        $stmt->execute([$course_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateVisibility($seminar_id, $visibility)
    {
        $stmt = DBManager::get()->prepare("UPDATE
                oc_seminar_series SET `visibility` = ?  WHERE `seminar_id` = ?");

        return $stmt->execute([$visibility, $seminar_id]);
    }

    public static function getVisibility($seminar_id)
    {
        $stmt = DBManager::get()->prepare("SELECT `visibility` FROM
                oc_seminar_series WHERE seminar_id = ?");
        $stmt->execute([$seminar_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function updateSchedule($seminar_id, $schedule)
    {
        $stmt = DBManager::get()->prepare("UPDATE
                oc_seminar_series SET `schedule` = ?  WHERE `seminar_id` = ?");

        return $stmt->execute([$schedule, $seminar_id]);
    }

    public static function getWorkflowForEvent($seminar_id, $termin_id)
    {
        $stmt = DBManager::get()->prepare('SELECT `workflow_id`FROM `oc_scheduled_recordings`
                            WHERE seminar_id = ? AND `date_id` = ?');
        $stmt->execute([$seminar_id, $termin_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getCoursesForSeries($series_id)
    {
        $stmt = DBManager::get()->prepare("SELECT seminar_id FROM oc_seminar_series WHERE series_id = ?;");
        $stmt->execute([$series_id]);

        return $stmt->fetchAll();
    }
}
