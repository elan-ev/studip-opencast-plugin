<?PHP
    
class OCModel
{

    static function getUnconnectedSeries() {
        $stmt = DBManager::get()->prepare("SELECT *
            FROM oc_series
            WHERE 1");
        $stmt->execute();
        $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $series;
    }
    
    static function getConnectedSeries($course_id) {
        $stmt = DBManager::get()->prepare("SELECT *
            FROM oc_seminar_series
            WHERE seminar_id = ?");
        $stmt->execute(array($course_id));
        $series = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $series;
    }
    
    static function setSeriesforCourse($course_id, $series_id, $visibility = 'visible', $schedule=0) {
        $stmt = DBManager::get()->prepare("UPDATE oc_series
                SET seminars = seminars+1
                WHERE series_id = ?");
        $stmt->execute(array($series_id));

        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_seminar_series (series_id, seminar_id, visibility, schedule)
                VALUES (?, ?, ?, ? )");
        return $stmt->execute(array($series_id, $course_id, $visibility, $schedule));
    }
    
    static function removeSeriesforCourse($course_id, $series_id) {
        $stmt = DBManager::get()->prepare("UPDATE 
                oc_series SET seminars = seminars-1
                WHERE series_id =?");
       $stmt->execute(array($course_id));
       $stmt = DBManager::get()->prepare("DELETE FROM
                oc_seminar_series
                WHERE series_id =? AND seminar_id = ?");
        return $stmt->execute(array($series_id, $course_id));
    }

    static function getOCRessources() {
       $stmt = DBManager::get()->prepare("SELECT * FROM resources_objects ro
                LEFT JOIN resources_objects_properties rop ON (ro.resource_id = rop.resource_id)
                WHERE rop.property_id = (SELECT property_id FROM resources_properties WHERE name = 'Opencast Capture Agent' )
                AND rop.state = 'on'");

       $stmt->execute();
       $resources =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       //return $resources;
       return "hallo";
    }

    static function getAssignedOCRessources() {
       $stmt = DBManager::get()->prepare("SELECT * FROM resources_objects ro
                LEFT JOIN resources_objects_properties rop ON (ro.resource_id = rop.resource_id)
                LEFT JOIN oc_resources ocr ON (ro.resource_id = ocr.resource_id)
                WHERE rop.property_id = (SELECT property_id FROM resources_properties WHERE name = 'Opencast Capture Agent' )
                AND rop.state = 'on'");

       $stmt->execute();
       $resources =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $resources;
    }

    static function setCAforResource($resource_id, $capture_agent) {
        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_resources (resource_id, capture_agent)
                VALUES (?, ?)");
        return $stmt->execute(array($resource_id, $capture_agent));
    }

    static function getCAforResource($resource_id) {
        $stmt = DBManager::get()->prepare("SELECT capture_agent FROM
                oc_resources WHERE resource_id = ?");
        $stmt->execute(array($resource_id));
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $agents;
    }

    static function removeCAforResource($resource_id, $capture_agent) {
        $stmt = DBManager::get()->prepare("DELETE FROM oc_resources
                WHERE resource_id =? AND capture_agent = ?");
        return $stmt->execute(array($resource_id, $capture_agent));
    }




    static function getAssignedCAS() {
        $stmt = DBManager::get()->prepare("SELECT * FROM
                oc_resources WHERE 1");
        $stmt->execute();
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $agents;
    }



    static function getMetadata($metadata, $type = 'title') {
        foreach($metadata->metadata as $data) {
            if($data->key == $type) {
                $return = $data->value;
            }
        }
       
        return $return;
    }

    static function getDates($seminar_id) {
        $stmt = DBManager::get()->prepare("SELECT * FROM `termine` WHERE `range_id` = ?");

       $stmt->execute(array($seminar_id));
       $dates =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $dates;
    }

    /**
     * checkResource - checks whether a resource has a CaptureAgent
     *
     * @param string $resource_id
     * @return boolean hasCA
     * 
     */

    static function checkResource($resource_id) {
       
       $stmt = DBManager::get()->prepare("SELECT * FROM `oc_resources` WHERE `resource_id` = ?");

       $stmt->execute(array($resource_id));
       if($ca = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            return $ca;
       } else {
            return false;
       }
       
    }

    /**
     * scheduleRecording - schedules a recording for a given date and resource within a course
     * 
     * @param string $course_id
     * @param string $resource_id
     * @param string $date_id
     * @return boolean success
     */

    static function scheduleRecording($course_id, $resource_id, $date_id, $event_id) {

        /* TODO
         *  - call Webservice and schedule that recording...
         */

        // 1st: retrieve series_id
        $series = self::getConnectedSeries($course_id);
        $serie = $series[0];

        $cas = self::checkResource($resource_id);
        $ca = $cas[0];
        

        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_scheduled_recordings (seminar_id,series_id, date_id,resource_id ,capture_agent, event_id, status)
                VALUES (?, ?, ?, ?, ?, ? ,? )");
        $success = $stmt->execute(array($course_id, $serie['series_id'],$date_id ,  $resource_id, $ca['capture_agent'], $event_id, 'scheduled'));

            
       

        return $success;
    }


    /**
     * unscheduleRecording -  removes a scheduled recording for a given date and resource within a course
     *
     * @param string $course_id
     * @param string $resource_id
     * @param string $date_id
     * @return boolean success
     */

    static function unscheduleRecording($event_id) {

        $stmt = DBManager::get()->prepare("DELETE FROM oc_scheduled_recordings
                WHERE event_id = ?");
        $success = $stmt->execute(array($event_id));


        return $success;
    }


    static function checkScheduled($course_id, $resource_id, $date_id) {

        $stmt = DBManager::get()->prepare("SELECT * FROM
                oc_scheduled_recordings 
                WHERE seminar_id = ?
                    AND date_id = ?
                    AND resource_id = ?
                    AND status = ?");
        $stmt->execute(array($course_id, $date_id ,  $resource_id,'scheduled'));
        $success = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $success;
    }

    /**
     * createSeriesXML - creates an xml representation for a new OC-Series
     * @param string $course_id
     * @return string xml - the xml representation of the string  
     */
    static function creatSeriesXML($course_id) {

        require_once 'lib/classes/Institute.class.php';
        $course = new Seminar($course_id);
        $name = $course->getName();
        $license = "Creative Commons"; // TODO
        $rightsHolder = $GLOBALS['UNI_NAME_CLEAN'];


        $inst = Institute::find($course->institut_id);
        $inst_data = $inst->getData();
        $publisher = $inst_data['name'];

        //$start = $course->getStartSemester();
        //$end = $course->getEndSemesterVorlesEnde();
        $audience = "General Public";

        $instructors = $course->getMembers('dozent');
        $instructor = array_pop($instructors);
        $contributor = $instructor['fullname'];
        $creator = $inst_data['name'];

        $language = 'German';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <series>
                        <description>' .$course->description . '</description>
                        <additionalMetadata>
                            <metadata>
                                <key>title</key>
                                <value>'. $name .'</value>
                            </metadata>
                            <metadata>
                                <key>license</key>
                                <value>'. $license . '</value>
                            </metadata>
                            <metadata>
                                <key>publisher</key>
                                <value>' . $publisher . '</value>
                            </metadata>
                            <metadata>
                                <key>creator</key>
                                <value>' . $creator . '</value>
                            </metadata>
                            <metadata>
                                <key>subject</key>
                                <value>'.  $course->description .'</value>
                            </metadata>
                            <metadata>
                                <key>temporal</key>
                                <value>demo</value>
                            </metadata>
                            <metadata>
                                <key>audience</key>
                                <value>' . $audience . '</value>
                            </metadata>
                            <metadata>
                                <key>spatial</key>
                                <value>demo</value>
                            </metadata>
                            <metadata>
                                <key>rightsHolder</key>
                                <value>' . $rightsHolder . '</value>
                            </metadata>
                            <metadata>
                                <key>extent</key>
                                <value>1314196388195</value>
                            </metadata>
                            <metadata>
                                <key>created</key>
                                <value>1314196388195</value>
                            </metadata>
                            <metadata>
                                <key>language</key>
                                <value>'. $language .'</value>
                            </metadata>
                            <metadata>
                                <key>isReplacedBy</key>
                                <value>demo</value>
                            </metadata>
                            <metadata>
                                <key>type</key>
                                <value>demo</value>
                            </metadata>
                            <metadata>
                                <key>available</key>
                                <value>1314196388195</value>
                            </metadata>
                            <metadata>
                                <key>modified</key>
                                <value>1314196388195</value>
                            </metadata>
                            <metadata>
                                <key>replaces</key>
                                <value>demo</value>
                            </metadata>
                            <metadata>
                                <key>contributor</key>
                                <value>' .$contributor . '</value>
                            </metadata>
                            <metadata>
                                <key>issued</key>
                                <value>1314196388195</value>
                            </metadata>
                        </additionalMetadata>
                    </series>';

        return $xml;
    }

    /**
     * createScheduleEventXML - creates an xml representation for a new OC-Series
     * @param string course_id
     * @param string resource_id
     * @param string $termin_id
     * @return string xml - the xml representation of the string
     */

     function createScheduleEventXML($course_id, $resource_id, $termin_id) {
        require_once 'lib/classes/Institute.class.php';

        date_default_timezone_set("Europe/Berlin");
        
        $course = new Seminar($course_id);
        $date = new SingleDate($termin_id);
        $issues = $date->getIssueIDs();
        
        if(is_array($issues)) {
            foreach($issues as $is) {
                $issue = new Issue(array('issue_id' => $is));
            }
        }
  

        $series = self::getConnectedSeries($course_id);
        $serie = $series[0];
        
        $cas = self::checkResource($resource_id);
        $ca = $cas[0];
        $instructors = $course->getMembers('dozent');
        $instructor = array_pop($instructors);

        $inst = Institute::find($course->institut_id);
        $inst_data = $inst->getData();

       

        $room = ResourceObject::Factory($resource_id);

        $start_time = $date->getStartTime();
        $end_time = $date->getEndTime();
        $start = $start_time.'000';
        $end = $end_time.'000';

        $duration = $end - $start;
        $duration = $duration;
        
        $duration_in_hours = $duration;

        $contributor = $inst_data['name'];
        $creator = $instructor['fullname'];
        $description = $issue->description;
        $device = $ca['capture_agent'];
        $duration = $duration_in_hours;
        $endDate = $end;
        $language = "German";
        $licence = "General PublicS";
        $resources  = 'vga, audio';
        $seriesId = $serie['series_id'];

        $startDate = $start;
        $title = $issue->title;
        // Additional Metadata
        $location = $room->name;
        $abstract = $course->description;

         $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <event>
                        <contributor>'.$contributor.'</contributor>
                        <creator>' . $creator . '</creator>
                        <description>' . $description .'</description>
                        <device>'. $device .'</device>
                        <duration>'. $duration .'</duration>
                        <endDate>' .$endDate . '</endDate>
                        <language>en</language>
                        <license>creative commons</license>
                        <resources>vga, audio</resources>
                        <seriesId>' . $seriesId.'</seriesId>
                        <series>' . $course->getName().'</series>
                        <startDate>'.$startDate . '</startDate>
                        <title>'. $title .'</title>
                        <additionalMetadata>
                            <metadata>
                                <key>location</key>
                                <value>' . $location . '</value>
                            </metadata>
                            <metadata>
                                <key>abstract</key>
                                <value>'. $abstract .'</value>
                            </metadata>
                        </additionalMetadata>
                    </event>';

         return $xml;

     }

    static function setVisibilityForEpisode($course_id, $episode_id, $visibility) {
        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_seminar_episodes (seminar_id, episode_id, visible)
                VALUES (?, ?, ?)");
        return $stmt->execute(array($course_id, $episode_id, $visibility));
    }

    static function getVisibilityForEpisode($course_id, $episode_id) {
        $stmt = DBManager::get()->prepare("SELECT visible FROM
                oc_seminar_episodes WHERE seminar_id = ? AND episode_id = ?");
        $stmt->execute(array($course_id, $episode_id));
        $episode = $stmt->fetch(PDO::FETCH_ASSOC);
        return $episode;
    }
}
?>
