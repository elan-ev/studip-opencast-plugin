<?php

use Opencast\Models\OCConfig;
use Opencast\Models\OCSeminarSeries;
use Opencast\Models\OCSeminarEpisodes;
use OpenCast\Models\OCScheduledRecordings;

use Opencast\LTI\OpencastLTI;

class OCModel
{
    public static function getOCRessources()
    {
        if (StudipVersion::newerThan('4.4'))
        {
            $stmt = DBManager::get()->prepare("SELECT * FROM resources ro
                LEFT JOIN resource_properties rop ON (ro.id = rop.resource_id)
                WHERE rop.property_id = ?
                AND rop.state = '1'");
        }
        else
        {
            $stmt = DBManager::get()->prepare("SELECT * FROM resources_objects ro
                LEFT JOIN resources_objects_properties rop ON (ro.resource_id = rop.resource_id)
                WHERE rop.property_id = ?
                AND rop.state = 'on'");
        }

        $stmt->execute([Config::get()->OPENCAST_RESOURCE_PROPERTY_ID]);
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resources;
    }

    public static function getAssignedOCRessources()
    {
        if (StudipVersion::newerThan('4.4'))
        {
            $stmt = DBManager::get()->prepare("SELECT * FROM resources ro
                LEFT JOIN resource_properties rop ON (ro.id = rop.resource_id)
                LEFT JOIN oc_resources ocr ON (ro.id = ocr.resource_id)
                WHERE rop.property_id = ?
                AND rop.state = 'on'");
        }
        else
        {
            $stmt = DBManager::get()->prepare("SELECT * FROM resources_objects ro
                LEFT JOIN resources_objects_properties rop ON (ro.resource_id = rop.resource_id)
                LEFT JOIN oc_resources ocr ON (ro.resource_id = ocr.resource_id)
                WHERE rop.property_id = ?
                AND rop.state = 'on'");
        }

        $stmt->execute([Config::get()->OPENCAST_RESOURCE_PROPERTY_ID]);
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resources;
    }

    public static function setCAforResource($resource_id, $capture_agent, $workflow_id)
    {
        $stmt = DBManager::get()->prepare("INSERT INTO
                oc_resources (resource_id, capture_agent, workflow_id)
                VALUES (?, ?, ?)");
        return $stmt->execute([$resource_id, $capture_agent, $workflow_id]);
    }

    public static function getCAforResource($resource_id)
    {
        $stmt = DBManager::get()->prepare("SELECT capture_agent, workflow_id FROM
                oc_resources WHERE resource_id = ?");
        $stmt->execute([$resource_id]);
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);

        return $agent;
    }

    public static function removeCAforResource($resource_id, $capture_agent)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM oc_resources
                WHERE resource_id =? AND capture_agent = ?");
        return $stmt->execute([$resource_id, $capture_agent]);
    }

    public static function getAssignedCAS()
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM
                oc_resources WHERE 1");
        $stmt->execute();
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $agents;
    }

    public static function getMetadata($metadata, $type = 'title')
    {
        foreach ($metadata->metadata as $data) {
            if ($data->key == $type) {
                $return = $data->value;
            }
        }
        return $return;
    }

    public static function getDates($seminar_id)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM `termine` WHERE `range_id` = ?");

        $stmt->execute([$seminar_id]);
        $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $dates;
    }

    public static function getDatesForSemester($seminar_id, $semester_id = null)
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

    public static function checkResource($resource_id)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM `oc_resources` WHERE `resource_id` = ?");

        $stmt->execute([$resource_id]);
        if ($ca = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
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
     * @param string $event_id - Opencast Identifier for the event
     * @return boolean success
     */

    public static function scheduleRecording($course_id, $resource_id, $date_id, $event_id)
    {
        // 1st: retrieve series_id
        $series = OCSeminarSeries::getSeries($course_id);
        $serie  = $series[0];

        $cas = self::checkResource($resource_id);
        $ca  = $cas[0];

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
    public static function checkScheduledRecording($course_id, $resource_id, $date_id)
    {
        $series = OCSeminarSeries::getSeries($course_id);
        $serie  = $series[0];

        $cas = self::checkResource($resource_id);
        $ca  = $cas[0];
        $stmt = DBManager::get()->prepare("SELECT * FROM oc_scheduled_recordings
                                                    WHERE `seminar_id` = ?
                                                    AND `series_id` = ?
                                                    AND `date_id` = ?
                                                    AND`resource_id` = ?");
        $stmt->execute([$course_id, $serie['series_id'], $date_id, $resource_id]);

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

    public static function unscheduleRecording($event_id)
    {
        $stmt    = DBManager::get()->prepare("DELETE FROM oc_scheduled_recordings
                WHERE event_id = ?");
        $success = $stmt->execute([$event_id]);


        return $success;
    }

    public static function checkScheduled($course_id, $resource_id, $date_id)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM
                oc_scheduled_recordings
                WHERE seminar_id = ?
                    AND date_id = ?
                    AND resource_id = ?
                    AND status = ?");
        $stmt->execute([$course_id, $date_id, $resource_id, 'scheduled']);
        $success = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $success;
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
        $date   = new SingleDate($termin_id);

        // if event_id is null, there is not yet an event which could have other start or end-times
        if ($event_id) {
            $event = OCScheduledRecordings::find($event_id);
        }

        $issues = $date->getIssueIDs();

        $issue_titles = [];
        if (is_array($issues)) {
            foreach ($issues as $is) {
                $issue = new Issue(['issue_id' => $is]);
                if (sizeof($issues) > 1) {
                    $issue_titles[] = my_substr($issue->getTitle(), 0, 80);
                } else $issue_titles = my_substr($issue->getTitle(), 0, 80);
            }
            if (is_array($issue_titles)) {
                $issue_titles = _("Themen: ") . my_substr(implode(', ', $issue_titles), 0, 80);
            }
        }


        $series = OCSeminarSeries::getSeries($course_id);
        $serie  = $series[0];

        $cas = self::checkResource($resource_id);
        $ca  = $cas[0];

        $creator = 'unknown';

        if ($GLOBALS['perm']->have_perm('admin')) {
            $instructors = $course->getMembers('dozent');
            $instructor  = array_shift($instructors);
            $creator     = $instructor['fullname'];
        } else {
            $creator = get_fullname();
        }

        $inst_data = Institute::find($course->institut_id);

        if (StudipVersion::newerThan('4.4')) {
        	$room = new Resource($resource_id);
        } else {
        	$room = ResourceObject::Factory($resource_id);
        }

        $start_time = $event_id ? $event->start : $date->getStartTime();

        if ($puffer) {
            $end_time = strtotime("-$puffer seconds ", intval($event_id ? $event->end : $date->getEndTime()));
        } else {
            $end_time = $event_id ? $event->end : $date->getEndTime();
        }

        $contributor = $inst_data['name'];
        $description = $issue->description;
        $device      = $ca['capture_agent'];

        $language = "de";
        $seriesId = $serie['series_id'];

        if (!$issue->title) {
            $course = new Seminar($course_id);
            $name   = $course->getName();
            $title  = $name . ' ' . sprintf(_('(%s)'), $date->getDatesExport());
        } else $title = $issue_titles;


        // Additional Metadata
        $location = $room->name;
        $abstract = $course->description;

        $dublincore = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                            <dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                <dcterms:creator><![CDATA[' .  urlencode($creator) . ']]></dcterms:creator>
                                <dcterms:contributor><![CDATA[' .  urlencode($contributor) . ']]></dcterms:contributor>
                                <dcterms:created xsi:type="dcterms:W3CDTF">' . self::getDCTime($start_time) . '</dcterms:created>
                                <dcterms:temporal xsi:type="dcterms:Period">start=' . self::getDCTime($start_time) . '; end=' . self::getDCTime($end_time) . '; scheme=W3C-DTF;</dcterms:temporal>
                                <dcterms:description><![CDATA[' . urlencode($description) . ']]></dcterms:description>
                                <dcterms:subject><![CDATA[' .  urlencode($abstract) . ']]></dcterms:subject>
                                <dcterms:language><![CDATA[' . $language . ']]></dcterms:language>
                                <dcterms:spatial>' . $device . '</dcterms:spatial>
                                <dcterms:title><![CDATA[' .  urlencode($title) . ']]></dcterms:title>
                                <dcterms:isPartOf>' . $seriesId . '</dcterms:isPartOf>
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
     * @param string $visibility invisible, visible or free
     *
     * @return bool
     */
    static function setVisibilityForEpisode($course_id, $episode_id, $visibility)
    {
        // Local
        $entry = self::getEntry($course_id, $episode_id);

        $default = Config::get()->OPENCAST_HIDE_EPISODES
            ? 'invisible'
            : 'visible';

        if ($entry) {
            $old_visibility = $entry->visible;
            $entry->visible = $visibility;
            // set a new chdate -- otherwise SimpleORMap::store() will
            // set the current timestamp which in turn will trigger the
            // safeguard in OpencastLTI::apply_acl_to_courses() and
            // changes aren't possible
            $entry->chdate += 1;

            $entry->store();

            $config_id = OCConfig::getConfigIdForCourse($course_id);

            // Remote: Try delete ACLs from Event if current visibility equals
            // default visibility
            // There is no point in changing ACLs prior to checking whether they
            // should be changed and then changing them, if they should!?
            //if ($visibility == $default) {
            //    $acl_manager = ACLManagerClient::getInstance($config_id);
            //    $acl_manager->applyACLto('episode', $episode_id, '');
            //}

            // This function is named "setVisibilityForEpisode" so
            // only set Episode visibility
            // Do not continue starting a workflow if changing ACLs did not
            // work!
            if (OpencastLTI::setAcls($course_id, $episode_id) === false) {
                return false;
            }

            $api = ApiWorkflowsClient::getInstance($config_id);

            if (!$api->republish($episode_id)) {
                // if republishing could not take place, reset permissions to previous state
                $entry->visible = $old_visibility;
                $entry->store();
                return false;
            }

            $entry->chdate = time();
            $entry->store();

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
            'series_id = ? AND episode_id = ? AND seminar_id = ?',
            [$series['series_id'], $episode_id, $course_id]
        );
    }

    static function getDCTime($timestamp)
    {
        return gmdate("Y-m-d", $timestamp) . 'T' . gmdate('H:i:s', $timestamp) . 'Z';
    }

    static function retrieveRESTservices($components, $match_protocol)
    {
        $services = [];
        foreach ($components as $service) {
            if (!preg_match('/remote/', $service->type)
                && !preg_match('#https?://localhost.*#', $service->host)
                && mb_strpos($service->host, $match_protocol) === 0
            ) {
                $services[preg_replace(["/\/docs/"], [''], $service->host . $service->path)]
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
        return $stmt->execute([$workflow_id, $seminar_id, $user_id, $mkdate]);
    }

    static function getWorkflowIDsforCourse($seminar_id)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM oc_seminar_workflows WHERE `seminar_id` = ?");
        $stmt->execute([$seminar_id]);

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
     * @param string $course_id
     * @param string $workflow_ids
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

        foreach ($table_entries as $table_entry) {
            $current_workflow_client   = WorkflowClient::getInstance(OCConfig::getConfigIdForCourse($table_entry['seminar_id']));
            $current_workflow_instance = $current_workflow_client->getWorkflowInstance($table_entry['workflow_id']);
            if ($current_workflow_instance->state == 'SUCCEEDED') {
                $states[$table_entry['seminar_id']][$table_entry['workflow_id']] = $current_workflow_instance->state;
                OCModel::removeWorkflowIDforCourse($table_entry['workflow_id'], $table_entry['seminar_id']);
            } else if ($current_workflow_instance) {
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
        return $stmt->execute([$seminar_id, $workflow_id]);
    }

    /**
     * Update or create episode with passed data
     *
     * @param [type]  $episode_id    [description]
     * @param [type]  $series_id     [description]
     * @param [type]  $seminar_id    [description]
     * @param [type]  $visibility    [description]
     * @param [type]  $mkdate        [description]
     *
     * @param boolean $is_retracting [description]
     */
    static function setEpisode($episode_id, $series_id, $seminar_id, $visibility, $is_retracting)
    {
        $is_new = false;

        // check, if entry already ; update if so, otherwise create entry
        $episode = OCSeminarEpisodes::findOneBySQL('episode_id = ?
            AND series_id = ? AND seminar_id = ?',
            [$episode_id, $series_id, $seminar_id]
        );

        if (!$episode) {
            $episode = new OCSeminarEpisodes();

            $episode->episode_id = $episode_id;
            $episode->series_id  = $series_id;
            $episode->seminar_id = $seminar_id;
            // allow ACL changes right now
            $episode->chdate     = 1;
            $episode->mkdate     = time();
            $is_new = true;
        }

        $episode->visible       = $visibility;
        $episode->is_retracting = $is_retracting;

        $episode->store();
        if ($is_new) {
          // apply correct ACLs to new Episode right now
          static::setVisibilityForEpisode($seminar_id, $episode_id, $visibility);
        }
    }

    static function getCoursePositions($course_id)
    {
        $stmt = DBManager::get()->prepare("SELECT series_id, episode_id,
            `visible`, `is_retracting`, mkdate
            FROM oc_seminar_episodes
            WHERE seminar_id = ?
            ORDER BY oc_seminar_episodes.mkdate DESC");
        $stmt->execute([$course_id]);

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

    static function checkPermForEpisode($episode_id, $user_id, $user_status)
    {
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) AS COUNT FROM oc_seminar_episodes oce
            JOIN oc_seminar_series oss USING (series_id)
            JOIN seminar_user su ON (oss.seminar_id = su.Seminar_id)
            WHERE oce.episode_id = ? AND su.status IN (?) AND su.user_id = ?");

        $stmt->execute([$episode_id, $user_status, $user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($rows['0'] > 0) {
            return true;
        }

        return false;
    }

    static function search_positions($array, $key, $value)
    {
        $results = [];

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

    static function getCoursesForEpisode($episode_id)
    {
        $stmt = DBManager::get()->prepare("SELECT DISTINCT seminar_id
            FROM oc_seminar_episodes
            WHERE episode_id = ?");
        $stmt->execute([$episode_id]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $return = [];

        foreach ($result as $entry) {
            $return[] = $entry['seminar_id'];
        }

        return $return;
    }

    static function getSeriesForEpisode($episode_id)
    {
        $courses = static::getCoursesForEpisode($episode_id);
        $series  = [];

        $stmt = DBManager::get()->prepare("SELECT series_id FROM oc_seminar_series WHERE seminar_id = ?");
        foreach ($courses as $course_id) {
            $stmt->execute([$course_id]);
            $direct_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($direct_result as $entry) {
                $series[] = $entry['series_id'];
            }
        }

        return array_unique($series);
    }
}
