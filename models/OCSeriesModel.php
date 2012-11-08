<?php

if (!class_exists('SeriesClient')) {
    throw new Exception('SeriesClient needs to be included before OCSeriesModel is included');
}

class OCSeriesModel {

    // saves all series for later requests
    static private $allSeries = null;
    // saves connected series for later requests
    static private $connectedSeries = null;
    // saves unconnected series for later requests
    static private $unconnectedSeries = null;
    // stores SiriesClient object for further calls
    static private $seriesClient = null;

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
        $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($series))
            return false;
        else
            return $series;
    }

    /**
     * checks for connected series in db than checks seriesID at REST if
     * refresh is true result of last call is overwritten otherwise last cals
     * result is returned withoud db or REST request 
     * 
     * @param string $courseID
     * @param bool $refresh
     * @return array
     */
    static function getConnectedSeries($courseID, $refresh = false) {
        //check if value assignment is needed
        if (is_null(self::$connectedSeries) || $refresh) {
            $sClient = self::getSeriesClient();

            $DBSeries = self::getConnectedSeriesDB($courseID);
            if ($DBSeries) {
                $res = array();
                foreach ($DBSeries as $series) {
                    if ($json = $sClient->getJSON('/series/' . $series['series_id'] . '.json')) {
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
     * if refresh is true result of last call is overwritten otherwise last cals
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
            $all = self::getAllSeries();

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
    static function getAllSeries($refresh = false) {
        //check if value assignment is needed
        if (is_null(self::$allSeries) || $refresh) {
            $sClient = self::getSeriesClient();
            $ret = array();
            if ($json = $sClient->getJSON('/series/series.json?q=*')) {
                foreach ($json->catalogs as $series) {
                    $ret[] = self::transformSeriesJSON($series);
                }
            }
            self::$allSeries = $ret;
        }
        return self::$allSeries;
    }

    /**
     * return series client
     * 
     * @return SeriesClient
     */
    static function getSeriesClient() {
        if (is_null(self::$seriesClient)) {
            self::$seriesClient = new SeriesClient();
            return self::$seriesClient;
        } else {
            return self::$seriesClient;
        }
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
     * write sireies for course in db
     * 
     * @param string $course_id
     * @param string $series_id
     * @param string $visibility
     * @param int $schedule
     * @return type
     */
    static function setSeriesforCourse($courseID, $seriesID, $visibility = 'visible', $schedule = 0) {
        $stmt = DBManager::get()->prepare("UPDATE oc_series
                SET seminars = seminars+1
                WHERE series_id = ?");
        $stmt->execute(array($seriesID));

        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_seminar_series (series_id, seminar_id, visibility, schedule)
                VALUES (?, ?, ?, ? )");
        return $stmt->execute(array($seriesID, $courseID, $visibility, $schedule));
    }

    /**
     * delete series connection to course
     * 
     * @param string $course_id
     * @param string $series_id
     * @return bool
     */
    static function removeSeriesforCourse($courseID, $seriesID) {
        $stmt = DBManager::get()->prepare("UPDATE 
                oc_series SET seminars = seminars-1
                WHERE series_id =?");
        $stmt->execute(array($courseID));
        $stmt = DBManager::get()->prepare("DELETE FROM
                oc_seminar_series
                WHERE series_id = ? AND seminar_id = ?");
        return $stmt->execute(array($seriesID, $courseID));
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
            if ($xml = self::$seriesClient->getXML('/series/' . $ser['identifier'] . '.xml')) {
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
            foreach ($perm as $key => $val) {
                $content[] = '<ace>'
                        . '<role>' . $role . '</role>'
                        . '<action>' . $key . '</action>'
                        . '<allow>' . $val . '</allow>'
                        . '</ace>';
            }
        }

        $str = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<acl xmlns="org.opencastproject.security">'
                . implode('', $content)
                . '</acl>';
        return $str;
    }

    /**
     * createSeriesXML - creates an xml representation for a new OC-Series
     * 
     * @param string $course_id
     * @return string xml - the xml representation of the string  
     */
    static function createSeriesDC($course_id) {

        require_once 'lib/classes/Institute.class.php';
        $course = new Seminar($course_id);
        $name = $course->getName();
        $license = "Creative Commons"; // TODO: Licence auswahl und Kommentare weg
//        $rightsHolder = $GLOBALS['UNI_NAME_CLEAN'];


        $inst = Institute::find($course->institut_id);
        $inst_data = $inst->getData();
        $publisher = $inst_data['name'];

        //$start = $course->getStartSemester();
        //$end = $course->getEndSemesterVorlesEnde();
        //       $audience = "General Public";
        //       $instructors = $course->getMembers('dozent');
        //       $instructor = array_pop($instructors);
        $contributor = $instructor['fullname'];
        $creator = $inst_data['name'];

        $language = 'German';

        $data = array(
            'title' => $name,
            'creator' => $creator,
            'contributor' => $contributor,
            'subject' => $course->description,
            'language' => $language,
            'license' => $license,
            'description' => $course->description,
            'publisher' => $publisher
        );
        $content = array();

        foreach ($data as $key => $val) {
            $content[] = '<dcterms:' . $key . ' xmlns="">' . $val . '</dcterms:' . $key . '>';
        }
        $str = '<?xml version="1.0"?>'
                . '<dublincore '
                . 'xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" '
                . 'xmlns:dcterms="http://purl.org/dc/terms/" '
                . 'xmlns:dc="http://purl.org/dc/elements/1.1/" '
                . 'xmlns:oc="http://www.opencastproject.org/matterhorn">'
                . implode('', $content)
                . '</dublincore>';

        return $str;
    }

}

?>
