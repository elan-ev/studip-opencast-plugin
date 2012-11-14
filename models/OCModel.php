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
        if (empty($series)) return false;
        else return $series;
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
                WHERE series_id = ? AND seminar_id = ?");
        return $stmt->execute(array($series_id, $course_id));
    }


    static function getOCRessources() {
       $stmt = DBManager::get()->prepare("SELECT * FROM resources_objects ro
                LEFT JOIN resources_objects_properties rop ON (ro.resource_id = rop.resource_id)
                WHERE rop.property_id = (SELECT property_id FROM resources_properties WHERE name = 'Opencast Capture Agent' )
                AND rop.state = 'on'");

       $stmt->execute();
       $resources =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $resources;
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


        $xml = '<?xml version="1.0"?>
                <dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance/"
                    xsi:schemaLocation="http://www.opencastproject.org http://www.opencastproject.org/schema.xsd" xmlns:dc="http://purl.org/dc/elements/1.1/"
                    xmlns:dcterms="http://purl.org/dc/terms/" xmlns:oc="http://www.opencastproject.org/matterhorn">

                    <dcterms:title xml:lang="en">
                        '. $name .'
                    </dcterms:title>
                    <dcterms:subject>
                        '.  $course->description .'
                    </dcterms:subject>
                    <dcterms:description xml:lang="en">
                        ' .$course->description . '
                    </dcterms:description>
                    <dcterms:creator>' . $publisher . '</dcterms:creator>
                    <dcterms:contributor>' . $contributor . '</dcterms:contributor>
                    <dcterms:publisher>
                        ' . $publisher . '
                    </dcterms:publisher>
                    <dcterms:identifier>
                        ' . $course_id . '
                    </dcterms:identifier>
                    <dcterms:modified xsi:type="dcterms:W3CDTF">
                        ' . date('Y-m-d',$course->metadate->seminarStartTime) . '
                    </dcterms:modified>
                    <dcterms:format xsi:type="dcterms:IMT">
                        video/x-dv
                    </dcterms:format>
                    <oc:promoted>
                        true
                    </oc:promoted>
                </dublincore>';

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
        /*
        $start = $start_time.'000';
        $end = $end_time.'000';

        $duration = $end - $start;
        $duration = $duration;
        
        $duration_in_hours = $duration;
        */

        $contributor = $inst_data['name'];
        $creator = $instructor['fullname'];
        $description = $issue->description;
        $device = $ca['capture_agent'];
        //$duration = $duration_in_hours;
        //$endDate = $end;
        $language = "German";
        $licence = "General PublicS";
        $resources  = 'vga, audio';
        $seriesId = $serie['series_id'];

       if(!$issue->title) {
           $title = sprintf(_('Aufzeichnung vom %s'), $date->getDatesExport());
       } else $title = $issue->title;


        // Additional Metadata
        $location = $room->name;
        $abstract = $course->description;

         /*$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
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

         */
         $dublincore = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                            <dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                <dcterms:creator>' . $creator . '</dcterms:creator>
                                <dcterms:contributor>' . $contributor . '</dcterms:contributor>
                                <dcterms:created xsi:type="dcterms:W3CDTF">2011-04-06T08:03Z</dcterms:created>
                                <dcterms:temporal xsi:type="dcterms:Period">start='. self::getDCTime($start_time) .' end='. self::getDCTime($end_time) .' scheme=W3C-DTF;</dcterms:temporal>
                                <dcterms:description>' . $description . '</dcterms:description>
                                <dcterms:subject>' . $abstract . '</dcterms:subject>
                                <dcterms:language>' . $language . '</dcterms:language>
                                <dcterms:spatial>' . $device . '</dcterms:spatial>
                                <dcterms:title>' . $title . '</dcterms:title>
                                <dcterms:isPartOf>'. $seriesId . '</dcterms:isPartOf>
                            </dublincore>';

         return $dublincore;

     }

    static function createACLXML() {

    $acl = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <ns2:acl xmlns:ns2="org.opencastproject.security">
                    <ace>
                        <role>admin</role>
                        <action>delete</action>
                        <allow>true</allow>
                     </ace>
                </ns2:acl>';

    return $acl;

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

    static function getDCTime($timestamp) {
        return date("Y-m-d", $timestamp).'T'.date('H:i:s', $timestamp).'Z;';
    }
}
?>
