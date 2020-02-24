<?php

use Opencast\Models\OCConfig;
use Opencast\Models\OCSeminarSeries;
use Opencast\Models\OCSeminarEpisodes;
use OpenCast\Models\OCScheduledRecordings;

use Opencast\LTI\OpencastLTI;

class OCModel
{
    static function getOCRessources()
    {
       $stmt = DBManager::get()->prepare("SELECT * FROM resources_objects ro
                LEFT JOIN resources_objects_properties rop ON (ro.resource_id = rop.resource_id)
                WHERE rop.property_id = ?
                AND rop.state = 'on'");

       $stmt->execute([Config::get()->OPENCAST_RESOURCE_PROPERTY_ID]);
       $resources =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $resources;
    }

    static function getAssignedOCRessources()
    {
       $stmt = DBManager::get()->prepare("SELECT * FROM resources_objects ro
                LEFT JOIN resources_objects_properties rop ON (ro.resource_id = rop.resource_id)
                LEFT JOIN oc_resources ocr ON (ro.resource_id = ocr.resource_id)
                WHERE rop.property_id = ?
                AND rop.state = 'on'");

       $stmt->execute([Config::get()->OPENCAST_RESOURCE_PROPERTY_ID]);
       $resources =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $resources;
    }

    static function setCAforResource($resource_id, $capture_agent, $workflow_id)
    {
        $stmt = DBManager::get()->prepare("INSERT INTO
                oc_resources (resource_id, capture_agent, workflow_id)
                VALUES (?, ?, ?)");
        return $stmt->execute(array($resource_id, $capture_agent, $workflow_id));
    }

    static function getCAforResource($resource_id)
    {
        $stmt = DBManager::get()->prepare("SELECT capture_agent, workflow_id FROM
                oc_resources WHERE resource_id = ?");
        $stmt->execute(array($resource_id));
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);

        return $agent;
    }

    static function removeCAforResource($resource_id, $capture_agent)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM oc_resources
                WHERE resource_id =? AND capture_agent = ?");
        return $stmt->execute(array($resource_id, $capture_agent));
    }


    static function getAssignedCAS()
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM
                oc_resources WHERE 1");
        $stmt->execute();
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $agents;
    }



    static function getMetadata($metadata, $type = 'title')
    {
        foreach($metadata->metadata as $data) {
            if($data->key == $type) {
                $return = $data->value;
            }
        }
        return $return;
    }

    static function getDates($seminar_id)
    {
       $stmt = DBManager::get()->prepare("SELECT * FROM `termine` WHERE `range_id` = ?");

       $stmt->execute(array($seminar_id));
       $dates =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $dates;
    }

    static function getDatesForSemester($seminar_id, $semester_id = null)
    {
        if ($semester_id == 'all' || is_null($semester_id)) {
            // get all dates
            $stmt = DBManager::get()->prepare("SELECT * FROM `termine`
                WHERE `range_id` = ?
                ORDER BY `date` ASC");
            $stmt->execute([$seminar_id]);
        } else {
            // get dates for selected semester only
            $semester = Semester::find($semester_id);

            $stmt = DBManager::get()->prepare("SELECT * FROM `termine`
                WHERE `range_id` = ?
                    AND `date` >= ?
                    AND `date` < ?
                ORDER BY `date` ASC");
            $stmt->execute([$seminar_id, $semester->beginn, $semester->ende]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * checkResource - checks whether a resource has a CaptureAgent
     *
     * @param string $resource_id
     * @return boolean hasCA
     *
     */

    static function checkResource($resource_id)
    {
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
     * @param string $date_id - Stud.IP Identifier for the event
     * @param string $event_id  - Opencast Identifier for the event
     * @return boolean success
     */

    static function scheduleRecording($course_id, $resource_id, $date_id, $event_id)
    {
        // 1st: retrieve series_id
        $series = OCSeminarSeries::getSeries($course_id);
        $serie = $series[0];

        $cas = self::checkResource($resource_id);
        $ca = $cas[0];

        $date = CourseDate::find($date_id);

        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_scheduled_recordings (seminar_id, series_id, date_id,
                    resource_id, start, end, capture_agent, event_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $success = $stmt->execute($data = [
            $course_id,
            $serie['series_id'],
            $date_id,
            $resource_id,
            $date->date,
            $date->end_time,
            $ca['capture_agent'],
            $event_id,
            'scheduled'
        ]);

        return $success;
    }

    /**
     * checkScheduledRecording - check if a recording is scheduled for a given date and resource within a course
     *
     * @param string $course_id
     * @param string $resource_id
     * @param string $date_id - Stud.IP Identifier for the event
     * @return boolean success
     */

    static function checkScheduledRecording($course_id, $resource_id, $date_id)
    {
        $series = OCSeminarSeries::getSeries($course_id);
        $serie = $series[0];

        $cas = self::checkResource($resource_id);
        $ca = $cas[0];


        $stmt = DBManager::get()->prepare("SELECT * FROM oc_scheduled_recordings
                                                    WHERE `seminar_id` = ?
                                                    AND `series_id` = ?
                                                    AND `date_id` = ?
                                                    AND`resource_id` = ?");
        $stmt->execute(array($course_id, $serie['series_id'],$date_id , $resource_id));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * unscheduleRecording -  removes a scheduled recording for a given date and resource within a course
     *
     * @param string $course_id
     * @param string $resource_id
     * @param string $date_id
     * @return boolean success
     */

    static function unscheduleRecording($event_id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM oc_scheduled_recordings
                WHERE event_id = ?");
        $success = $stmt->execute(array($event_id));


        return $success;
    }


    static function checkScheduled($course_id, $resource_id, $date_id)
    {
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
    static function creatSeriesXML($course_id)
    {
        $course = new Seminar($course_id);

        $name = $course->getName();
        $license = "All Rights Reserved";
        $rightsHolder = $GLOBALS['UNI_NAME_CLEAN'];


        $inst = Institute::find($course->institut_id);
        $inst_data = $inst->getData();
        $publisher = $inst_data['name'];

        //$start = $course->getStartSemester();
        //$end = $course->getEndSemesterVorlesEnde();
        $audience = "General Public";

        $instructors = $course->getMembers('dozent');
        $instructor = array_shift($instructors);
        $contributor = $instructor['fullname'];
        $creator = $inst_data['name'];

        $language = 'de';


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
     public static function createScheduleEventXML($course_id, $resource_id, $termin_id, $event_id, $puffer)
     {
        date_default_timezone_set("Europe/Berlin");

        $course = new Seminar($course_id);
        $date = new SingleDate($termin_id);

        // if event_id is null, there is not yet an event which could have other start or end-times
        if ($event_id) {
            $event = OCScheduledRecordings::find($event_id);
        }

        $issues = $date->getIssueIDs();

         $issue_titles = array();
         if(is_array($issues)) {
             foreach($issues as $is) {
                 $issue = new Issue(array('issue_id' => $is));
                 if(sizeof($issues) > 1) {
                     $issue_titles[] =  my_substr($issue->getTitle(), 0 ,80 );
                 } else $issue_titles =  my_substr($issue->getTitle(), 0 ,80 );
             }
             if(is_array($issue_titles)){
                 $issue_titles = _("Themen: ") . my_substr(implode(', ', $issue_titles), 0 ,80 );
             }
         }


        $series = OCSeminarSeries::getSeries($course_id);
        $serie = $series[0];

        $cas = self::checkResource($resource_id);
        $ca = $cas[0];

        $creator = 'unknown';

        if ($GLOBALS['perm']->have_perm('admin')) {
            $instructors = $course->getMembers('dozent');
            $instructor = array_shift($instructors);
            $creator    = $instructor['fullname'];
        } else {
            $creator    = get_fullname();
        }

        $inst_data = Institute::find($course->institut_id);

        $room = ResourceObject::Factory($resource_id);

        $start_time = $event_id ? $event->start : $date->getStartTime();

        if ($puffer) {
            $end_time = strtotime("-$puffer seconds ", intval($event_id ? $event->end : $date->getEndTime()));
        } else {
            $end_time = $event_id ? $event->end : $date->getEndTime();
        }

        $contributor = $inst_data['name'];
        $description = $issue->description;
        $device = $ca['capture_agent'];

        $language = "de";
        $seriesId = $serie['series_id'];

       if (!$issue->title) {
           $course = new Seminar($course_id);
           $name = $course->getName();
           $title = $name . ' ' . sprintf(_('(%s)'), $date->getDatesExport());
       } else $title = $issue_titles;


        // Additional Metadata
        $location = $room->name;
        $abstract = $course->description;

        $dublincore = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                            <dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                <dcterms:creator><![CDATA[' . $creator . ']]></dcterms:creator>
                                <dcterms:contributor><![CDATA[' . $contributor . ']]></dcterms:contributor>
                                <dcterms:created xsi:type="dcterms:W3CDTF">' . self::getDCTime($start_time) . '</dcterms:created>
                                <dcterms:temporal xsi:type="dcterms:Period">start='. self::getDCTime($start_time) .'; end='. self::getDCTime($end_time) .'; scheme=W3C-DTF;</dcterms:temporal>
                                <dcterms:description><![CDATA[' . $description . ']]></dcterms:description>
                                <dcterms:subject><![CDATA[' . $abstract . ']]></dcterms:subject>
                                <dcterms:language><![CDATA[' . $language . ']]></dcterms:language>
                                <dcterms:spatial>' . $device . '</dcterms:spatial>
                                <dcterms:title><![CDATA[' . $title . ']]></dcterms:title>
                                <dcterms:isPartOf>'. $seriesId . '</dcterms:isPartOf>
                            </dublincore>';

        return $dublincore;

     }

    static function createACLXML()
    {

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

    /**
     * Set episode visibility
     *
     * @param string $course_id
     * @param string $episode_id
     * @param string $visibility   invisible, visible or free
     *
     * @return bool
     */
    static function setVisibilityForEpisode($course_id, $episode_id, $visibility)
    {
        // Local
        $entry = self::getEntry($course_id, $episode_id);

        if ($entry) {
            $old_visibility = $entry->visible;
            $entry->visible = $visibility;

            $entry->store();

            $config_id = OCConfig::getConfigIdForCourse($course_id);

            // Remote
            if ($visibility == 'visible') {
                $acl_manager = ACLManagerClient::getInstance($config_id);
                $acl_manager->applyACLto('episode', $episode_id, '');
            }

            OpencastLTI::setAcls($course_id);

            $api = ApiWorkflowsClient::getInstance($config_id);

            if (!$api->republish($episode_id)) {
                // if republishing could not take place, reset permissions to previous state
                $entry->visible = $old_visibility;
                $entry->store();
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * get visibility row
     *
     * @param string $course_id
     * @param string $episode_id
     * @return array
     */
    static function getEntry($course_id, $episode_id)
    {
        $series = reset(OCSeminarSeries::getSeries($course_id));

        return OCSeminarEpisodes::findOneBySQL(
            'series_id = ? AND episode_id = ?',
            [$series['series_id'], $episode_id]
        );
    }

    static function getDCTime($timestamp)
    {
        return gmdate("Y-m-d", $timestamp).'T'.gmdate('H:i:s', $timestamp).'Z';
    }

    static function retrieveRESTservices($components, $match_protocol)
    {
        $services = array();
        foreach ($components as $service) {
            if (!preg_match('/remote/', $service->type)
                && !preg_match('#https?://localhost.*#', $service->host)
                && mb_strpos($service->host, $match_protocol) === 0
            ) {
                $services[preg_replace(array("/\/docs/"), array(''), $service->host.$service->path)]
                         = preg_replace("/\//", '', $service->path);
            }
        }

        return $services;
    }

    static function setWorkflowIDforCourse($workflow_id, $seminar_id, $user_id, $mkdate)
    {
        $stmt = DBManager::get()->prepare("INSERT INTO
                oc_seminar_workflows (workflow_id, seminar_id, user_id, mkdate)
                VALUES (?, ?, ?, ?)");
        return $stmt->execute(array($workflow_id, $seminar_id, $user_id, $mkdate));
    }

    static function getWorkflowIDsforCourse($seminar_id)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM oc_seminar_workflows WHERE `seminar_id` = ?");
        $stmt->execute(array($seminar_id));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static function getWorkflowIDs()
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM oc_seminar_workflows");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * returns the state of all running workflow and even removes broken ones
     *
     * @param  string $course_id
     * @param  string $workflow_ids
     * @return mixed
     */
    static function getWorkflowStates($course_id, $workflow_ids)
    {
        $states = static::getWorkflowStatesFromSQL($workflow_ids);

        if (isset($states[$course_id])) {
            return $states[$course_id];
        }

        return [];
    }

    static function getWorkflowStatesFromSQL($table_entries)
    {
        $states = [];

        foreach ($table_entries as $table_entry){
            $current_workflow_client = WorkflowClient::getInstance(OCConfig::getConfigIdForCourse($table_entry['seminar_id']));
            $current_workflow_instance = $current_workflow_client->getWorkflowInstance($table_entry['workflow_id']);
            if ($current_workflow_instance->state == 'SUCCEEDED') {
                $states[$table_entry['seminar_id']][$table_entry['workflow_id']] = $current_workflow_instance->state;
                OCModel::removeWorkflowIDforCourse($table_entry['workflow_id'], $table_entry['seminar_id']);
            } else if($current_workflow_instance) {
                $states[$table_entry['seminar_id']][$table_entry['workflow_id']] = $current_workflow_instance;
            } else {
                OCModel::removeWorkflowIDforCourse($table_entry['workflow_id'], $table_entry['seminar_id']);
            }
        }

        return $states;
    }

    static function removeWorkflowIDforCourse($workflow_id, $seminar_id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM
                 oc_seminar_workflows
                 WHERE `seminar_id` = ? AND `workflow_id`= ?");
         return $stmt->execute(array($seminar_id, $workflow_id));
    }

    static function setEpisode($episode_id, $series_id, $visibility, $mkdate)
    {
        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_seminar_episodes (`series_id`,`episode_id`, `visible`, `mkdate`)
                VALUES (?, ?, ?, ?)");

        return $stmt->execute(array($series_id, $episode_id, $visibility, $mkdate));
    }

    static function getCoursePositions($course_id)
    {
        $stmt = DBManager::get()->prepare("SELECT series_id, episode_id,
            `visible`, oc_seminar_episodes.mkdate
            FROM oc_seminar_series
            JOIN oc_seminar_episodes USING (series_id)
            WHERE `seminar_id` = ? ORDER BY oc_seminar_episodes.mkdate DESC");
        $stmt->execute(array($course_id));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static function getConfigurationstate()
    {
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) AS COUNT FROM oc_endpoints");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($rows['0'] > 0) {
            return true;
        }

        return false;
    }

    static function checkPermForEpisode($episode_id, $user_id)
    {
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) AS COUNT FROM oc_seminar_episodes oce
            JOIN oc_seminar_series oss USING (series_id)
            JOIN seminar_user su ON (oss.seminar_id = su.Seminar_id)
            WHERE oce.episode_id = ? AND su.status = 'dozent' AND su.user_id = ?");

        $stmt->execute(array($episode_id, $user_id));
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($rows['0'] > 0) {
            return true;
        }

        return false;
    }

    static function search_positions($array, $key, $value)
    {
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, self::search_positions($subarray, $key, $value));
            }
        }

        return $results;
    }

    static function removeStoredEpisode($episode_id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM `oc_seminar_episodes`
            WHERE `episode_id` = ?");

        return $stmt->execute([$episode_id]);
    }

    static function getCoursesForEpisode($episode_id){
        $stmt = DBManager::get()->prepare("SELECT seminar_id
            FROM oc_seminar_episodes
            JOIN oc_seminar_series USING (series_id)
            WHERE episode_id = ?");
        $stmt->execute([$episode_id]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $return = [];

        foreach ($result as $entry){
            $return[] = $entry['seminar_id'];
        }

        return array_unique($return);
    }

    static function getSeriesForEpisode($episode_id){
        $courses = static::getCoursesForEpisode($episode_id);
        $series = [];

        $stmt = DBManager::get()->prepare("SELECT series_id FROM oc_seminar_series WHERE seminar_id = ?");
        foreach ($courses as $course_id){
            $stmt->execute([$course_id]);
            $direct_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($direct_result as $entry){
                $series[] = $entry['series_id'];
            }
        }

        return array_unique($series);
    }
}
