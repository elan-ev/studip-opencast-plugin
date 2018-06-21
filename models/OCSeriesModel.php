<?php
require_once dirname(__FILE__) .'/../classes/OCRestClient/SeriesClient.php';
//if (!class_exists('SeriesClient')) {
//    throw new Exception('SeriesClient needs to be included before OCSeriesModel is included');
//}

class OCSeriesModel {

    // saves all series for later requests
    static private $allSeries = null;
    // saves connected series for later requests
    static private $connectedSeries = null;
    // saves unconnected series for later requests
    static private $unconnectedSeries = null;

    /**
     * return connected Siries for $courseID from DB
     *
     * @param string $courseID
     * @return array
     */
    static function getConnectedSeriesDB($courseID) {
        $stmt = DBManager::get()->prepare("SELECT *
            FROM oc_seminar_series
            WHERE seminar_id = ?");
        $stmt->execute(array($courseID));

        if ($series = $stmt->fetchAll(PDO::FETCH_ASSOC))
            return $series;
        else
            return false;
    }

    static function getSeminarAndSeriesData(){
        $stmt = DBManager::get()->prepare("SELECT * FROM oc_seminar_series WHERE schedule = '1';");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * checks for connected series in db than checks seriesID at REST if
     * refresh is true result of last call is overwritten otherwise last calls
     * result is returned without db or REST request
     *
     * @param string $courseID
     * @param bool $refresh
     * @return array
     */
    static function getConnectedSeries($courseID, $refresh = false) {
        //check if value assignment is needed
        if (is_null(self::$connectedSeries) || $refresh) {
            $sClient = SeriesClient::getInstance($courseID);
            $DBSeries = self::getConnectedSeriesDB($courseID);
            if ($DBSeries) {
                $res = array();
                foreach ($DBSeries as $series) {
                    if ($json = $sClient->getOneSeries($series['series_id'])) {
                        $res[] = self::transformSeriesJSON($json);
                    }
                }
                self::$connectedSeries = $res;
            } else {
                self::$connectedSeries = array();
            }
        }
        return self::$connectedSeries;
    }




    /**
     * return unconnected series
     * if refresh is true result of last call is overwritten otherwise last calls
     * result is returned
     *
     * @param string $courseID
     * @param bool $refresh
     * @return array
     */
    static function getUnconnectedSeries($courseID, $refresh = false) {
        //check if value assignment is needed
        if (is_null(self::$unconnectedSeries) || $refresh) {
            $connected = self::getConnectedSeries($courseID);
            $all = self::getAllSeries($refresh);

            if (empty($connected)) {
                self::$unconnectedSeries = $all;
            } elseif (empty($all)) {
                self::$unconnectedSeries = array();
            } else {
                $connectedIdentifier = array();
                //get all identifier of connected siries in one array
                foreach ($connected as $con) {
                    $connectedIdentifier[] = $con['identifier'];
                }
                //compare connected to all and delete connected
                foreach ($all as $val => $key) {
                    if (in_array($key['identifier'], $connectedIdentifier)) {
                        unset($all[$val]);
                    }
                }
                sort($all);
                self::$unconnectedSeries = $all;
            }
        }
        return self::$unconnectedSeries;
    }

    /**
     * return all unconnected series
     * if refresh is true result of last call is overwritten otherwise last cals
     * result is returned
     *
     * @param bool $refresh
     * @return array
     */
    static function getAllSeries($refresh = false)
    {
        //check if value assignment is needed
        if (is_null(self::$allSeries) || $refresh) {
            $sClient = SeriesClient::getInstance();
            $ret = array();
            if ($json = $sClient->getAllSeries()) {

                foreach ($json as $series) {
                    $ret[] = self::transformSeriesJSON($series);
                }
            }
            self::$allSeries = $ret;
        }
        return self::$allSeries;
    }


    /**
     * transforms multidimensional series array into 2 dimensional array
     *
     * @param array $data
     * @return array
     */
    static private function transformSeriesJSON($data) {
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
    static function setSeriesforCourse($courseID, $seriesID, $visibility = 'visible', $schedule = 0, $mkdate = 0) {
        $configID = SeriesClient::getConfigIdForCourse($courseID);
        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_seminar_series (config_id, series_id, seminar_id, visibility, schedule, mkdate)
                VALUES (?, ?, ?, ?, ?, ? )");
        return $stmt->execute(array($configID,$seriesID, $courseID, $visibility, $schedule, $mkdate));
    }

    /**
     * delete series connection to course
     *
     * @param string $course_id
     * @param string $series_id
     * @return bool
     */
    static function removeSeriesforCourse($courseID, $seriesID) {
        $qepisodes =  DBManager::get()->prepare("DELETE FROM oc_seminar_episodes WHERE seminar_id = ?");
        if($qepisodes->execute(array($courseID))){
            $stmt = DBManager::get()->prepare("DELETE FROM
                oc_seminar_series
                WHERE series_id = ? AND seminar_id = ?");
            return $stmt->execute(array($seriesID, $courseID));
    }
        else return false;
    }

    /**
     * return array with connected series dublin core xml
     *
     * @param string $courseID
     * @return array
     */
    static function getSeriesDCs($courseID) {
        $series = self::getConnectedSeries($courseID);
        $ret = array();
        foreach ($series as $ser) {
            if ($xml = SeriesClient::getInstance($courseID)->getXML('/' . $ser['identifier'] . '.xml')) {
                $ret[] = $xml;
            }
        }
        return $ret;
    }

    /**
     * Set episode visibility
     *
     * @param string $course_id
     * @param string $episode_id
     * @param tyniint 1 or 0
     * @return bool
     */
    static function setVisibilityForEpisode($course_id, $episode_id, $visibility) {
        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_seminar_episodes (seminar_id, episode_id, visible)
                VALUES (?, ?, ?)");
        return $stmt->execute(array($course_id, $episode_id, $visibility));
    }

    /**
     * get visibility row
     *
     * @param string $course_id
     * @param string $episode_id
     * @return array
     */
    static function getVisibilityForEpisode($course_id, $episode_id) {
        $stmt = DBManager::get()->prepare("SELECT visible FROM
                oc_seminar_episodes WHERE seminar_id = ? AND episode_id = ?");
        $stmt->execute(array($course_id, $episode_id));
        $episode = $stmt->fetch(PDO::FETCH_ASSOC);
        return $episode;
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
    static function createSeriesACL($data) {


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

        $str = studip_utf8encode('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<acl xmlns="http://org.opencastproject.security">'
                . implode('', $content)
                . '</acl>');
        return $str;
    }

    /**
     * createSeriesDC - creates an xml representation for a new OC-Series
     *
     * @param string $course_id
     * @return string xml - the xml representation of the string
     */
    static function createSeriesDC($course_id) {

        if (version_compare($GLOBALS['SOFTWARE_VERSION'], "3.3", '<=')) {
            require_once 'lib/classes/Institute.class.php';
        } else {
            require_once 'lib/models/Institute.class.php';
        }

        $course = new Seminar($course_id);
        $name = $course->getName();
        $license = "&copy; " . gmdate(Y) . " " . $GLOBALS['UNI_NAME_CLEAN'];
        $rightsHolder = $GLOBALS['UNI_NAME_CLEAN'];

        $inst = Institute::find($course->institut_id);

        $publisher = $inst->name;
        $start = $course->getStartSemester();
        $end = $course->getEndSemesterVorlesEnde();
        $audience = "General Public";
        $instructors = $course->getMembers('dozent');
        $instructor = array_shift($instructors);
        $contributor = $GLOBALS['UNI_NAME_CLEAN'];
        $creator = $instructor['fullname'];
        $language = 'de';

        if (mb_strlen($course->description) > 1000){
                $description .= studip_substr($course->description, 0, 1000);
                $description .= "... ";
        } else {
            $description = $course->description;
        }

        $data = array(
            'title' => $name,
            'creator' => $creator,
            'contributor' => $contributor,
            'subject' => $course->form,
            'language' => $language,
            'license' => $license,
            'description' => $description,
            'publisher' => $publisher
        );


        $content = array();

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

    static function getScheduledEpisodes($course_id) {
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

    static function setCachedSeriesData($series_id, $data) {
        $stmt = DBManager::get()->prepare("INSERT INTO
                oc_series_cache (`series_id`, `content`, `mkdate`, `chdate`)
                VALUES (?, ?, ?, ?)");
        return $stmt->execute(array($series_id, $data, time() ,time()));
    }

    static function updateCachedSeriesData($series_id, $data) {
        $stmt = DBManager::get()->prepare("UPDATE
                oc_series_cache SET `content` = ?, `chdate`= ? WHERE `series_id` = ?");
        return $stmt->execute(array($data, time(), $series_id));
    }

    static function clearCachedSeriesData()
    {
        DBManager::get()->exec("TRUNCATE oc_series_cache");
    }

    static function updateVisibility($seminar_id,$visibility){
        $stmt = DBManager::get()->prepare("UPDATE
                oc_seminar_series SET `visibility` = ?  WHERE `seminar_id` = ?");
        return $stmt->execute(array($visibility, $seminar_id));
    }

    static function getVisibility($seminar_id){
        $stmt = DBManager::get()->prepare("SELECT `visibility` FROM
                oc_seminar_series WHERE seminar_id = ?");
        $stmt->execute(array($seminar_id));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static function getWorkflowForEvent($seminar_id, $termin_id ){
        $stmt = DBManager::get()->prepare('SELECT `workflow_id`FROM `oc_scheduled_recordings`
                            WHERE seminar_id = ? AND `date_id` = ?');
        $stmt->execute(array($seminar_id, $termin_id));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

?>
