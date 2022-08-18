<?php

namespace Opencast\Models;

class ScheduledRecordings extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_scheduled_recordings';

        parent::configure($config);
    }

    /**
     * Adds or updates schedule recording
     * 
     * @param string $seminar_id id of course
     * @param string $series_id id of serie
     * @param string $date_id id of termin
     * @param string $resource_id id of resource
     * @param int $start start timestamp
     * @param int $end end timestamp
     * @param string $capture_agent capture agent name
     * @param string $event_id id of oc event
     * @param string $status the status of the record
     * @param string $workflow_id workflow id
     * 
     * @return bool
     * @throws Exception
     */
    public static function setScheduleRecording(
        $seminar_id,
        $series_id,
        $date_id,
        $resource_id,
        $start,
        $end,
        $capture_agent,
        $event_id,
        $status = 'scheduled',
        $workflow_id = 'full')
    {
        if (!empty($seminar_id) && !empty($resource_id) && !empty($date_id)) {
            if (!$scheduled_recording = self::getScheduleRecording($seminar_id, $resource_id, $date_id)) {
                $scheduled_recording = new self();
            }

            $scheduled_recording->setData(compact(
                'seminar_id',
                'series_id',
                'date_id',
                'resource_id',
                'start',
                'end',
                'capture_agent',
                'event_id',
                'status',
                'workflow_id',
            ));
            return $scheduled_recording->store();
        } else {
            throw new Exception(_('Der geplante Termin wurden nicht korrekt angegeben.'));
        }
    }

    /**
     * Gets the schedule recording record
     * 
     * @param string $seminar_id id of course
     * @param string $resource_id id of resource
     * @param string $date_id id of termin
     * @param string $status the status of the record
     * 
     * @return object|bool
     */
    public static function getScheduleRecording($seminar_id, $resource_id, $date_id, $status = '')
    {
        $where_array = ["seminar_id = ?", "date_id = ?", "resource_id = ?"];
        $params = [$seminar_id, $date_id ,  $resource_id];
        if (!empty($status)) {
            $where_array[] = "status = ?";
            $params[] = $status;
        }
        return self::findOneBySQL(implode(' AND ', $where_array), $params);
    }

    /**
     * Gets the list of schedule recording based on the parameters passed
     * 
     * @param string $resource_id id of resource
     * @param string $seminar_id id of course
     * @param string $status the status of the record
     * 
     * @return object|bool
     */
    public static function getScheduleRecordingList($resource_id = null, $seminar_id = null, $status = '')
    {
        $where_array = [];
        $params = [];
        if (!empty($resource_id)) {
            $where_array[] = "resource_id = ?";
            $params[] = $resource_id;
        }
        if (!empty($seminar_id)) {
            $where_array[] = "seminar_id = ?";
            $params[] = $seminar_id;
        }
        if (!empty($status)) {
            $where_array[] = "status = ?";
            $params[] = $status;
        }
        if (!empty($where_array)) {
            return self::findBySQL(implode(' AND ', $where_array), $params);
        }
        return self::findBySQL(1);
    }

    /**
     * Removes a scheduled recording for a given date and resource within a course
     *
     * @param string $event_id id of oc event
     * @param string $resource_id id of resource
     * @param string $date_id id of termin
     * 
     * @return boolean
     */

    public static function unscheduleRecording($event_id, $resource_id = null, $date_id = null)
    {
        $params = [$event_id];
        $where_array = ['event_id = ?'];

        if (!empty($resource_id)) {
            $where_array[] = 'resource_id = ?';
            $params[] = $resource_id;
        }
        if (!empty($date_id)) {
            $where_array[] = 'date_id = ?';
            $params[] = $date_id;
        }

        return self::deleteBySql(implode(' AND ', $where_array), $params);
    }

    /**
     * Get scheduled recording object with scheduled as its status
     * 
     * @param string $event_id id of oc event
     * @param string $resource_id id of resource
     * @param string $date_id id of termin
     * 
     * @return object|null
     */
    public static function checkScheduled($course_id, $resource_id, $date_id)
    {
        return self::getScheduleRecording($course_id, $resource_id, $date_id, 'scheduled');
    }
}
