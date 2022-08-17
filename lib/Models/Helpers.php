<?php

namespace Opencast\Models;

use \DBManager;
use \PDO;
use \Configuration as StudipConfiguration;

use Opencast\LTI\OpencastLTI;

class Helpers
{
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

            $config_id = Config::getConfigIdForCourse($course_id);

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
        $series = reset(SeminarSeries::getSeries($course_id));

        return OCSeminarEpisodes::findOneBySQL(
            'series_id = ? AND episode_id = ?',
            [$series['series_id'], $episode_id]
        );
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
            $current_workflow_client = WorkflowClient::getInstance(Config::getConfigIdForCourse($table_entry['seminar_id']));
            $current_workflow_instance = $current_workflow_client->getWorkflowInstance($table_entry['workflow_id']);
            if ($current_workflow_instance->state == 'SUCCEEDED') {
                $states[$table_entry['seminar_id']][$table_entry['workflow_id']] = $current_workflow_instance->state;
                Helpers::removeWorkflowIDforCourse($table_entry['workflow_id'], $table_entry['seminar_id']);
            } else if($current_workflow_instance) {
                $states[$table_entry['seminar_id']][$table_entry['workflow_id']] = $current_workflow_instance;
            } else {
                Helpers::removeWorkflowIDforCourse($table_entry['workflow_id'], $table_entry['seminar_id']);
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
        // TODO
        return true;

        $stmt = DBManager::get()->prepare("SELECT COUNT(*) AS c FROM oc_config");
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
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
