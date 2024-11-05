<?php

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';


use \Config as StudipConfig;
use Opencast\Models\Config;
use Opencast\Models\REST\ApiEventsClient;
use Opencast\Models\ScheduledRecordings;
use Opencast\Models\Resources;
use Opencast\Models\ScheduleHelper;
use Opencast\Models\REST\SchedulerClient;

class OpencastRefreshScheduling extends CronJob
{

    /**
     * Return the name of the cronjob.
     */
    public static function getName()
    {
        return _('Opencast - Aufzeichnungsplanung aktualisieren');
    }

    /**
     * Return the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Opencast: Aktualisiert alle geplanten Aufzeichnungen der in Stud.IP verbundenen Serien und löscht geplante Events, die nicht mehr benötigt werden.');
    }

    public function execute($last_result, $parameters = array())
    {
        // if scheduling is disabled in the config, do NOT run this cronjob!
        if (!StudipConfig::get()->OPENCAST_ALLOW_SCHEDULER) {
            return;
        }

        $oc_scheduled_events = [];
        $config = Config::findBySql(1);
        $oc_se_count = 0;

        // 1. Get Opencast Scheduled Recordings based on each configured server config.
        foreach ($config as $conf) {
            $config_id = $conf['id'];
            $scheduled_events = [];

            try {
                $events_client = ApiEventsClient::getInstance($config_id);
                // Adding config_id to each record for easier use later on!
                $scheduled_events = array_map(function ($event) use ($config_id) {
                    $event->config_id = $config_id;
                    return $event;
                }, $events_client->getAllScheduledEvents());
            } catch (\Throwable $th) {
                echo 'Fehler beim abrufen der Events für config_id '. $config_id
                    .': '. $th->getMessage() . "\n";
            }

            $oc_scheduled_events[] = [
                'config_id' => $config_id,
                'scheduled_events' => $scheduled_events
            ];
            $oc_se_count += count($scheduled_events);
        }
        echo 'In Opencast geplante Events: ' . $oc_se_count . "\n";

        // 2. Get all scheduled recordings stored in StudIP.
        $sop_scheduled_events = ScheduledRecordings::findBySql(1);
        echo 'In SOP geplante Events: ' . sizeof($sop_scheduled_events) . "\n";

        $time = time();
        // 3. Loop through every SOP records, to validate the record.
        foreach ($sop_scheduled_events as $scheduled_events) {
            try {
                $cd = \CourseDate::find($scheduled_events['date_id']);
                $course = \Course::find($scheduled_events['seminar_id']);
                $course_config_id = Config::getConfigIdForCourse($scheduled_events['seminar_id']);
                $resource_obj = Resources::findByResource_id($scheduled_events['resource_id']);

                // Validate SOP resource.
                // In case validation fails, we try to remove the record on both sides!
                if (!$cd || !$course || !$course_config_id || !$resource_obj                                                    // Any requirement fails
                    || !ScheduleHelper::validateCourseAndResource($scheduled_events['seminar_id'], $resource_obj['config_id'])  // The server config id of the course and the oc_resource does not match
                    || $cd->room_booking->resource_id != $scheduled_events['resource_id']                                       // The resource of the record and course date does not match
                    || $cd->room_booking->begin != $scheduled_events['start']                                                      // Start or Enddate are different
                    || $cd->room_booking->end != $scheduled_events['end']
                    /* || intval($cd->end_time) < $time */                                                                      // TODO: decide whether to remove those records that are expired!
                    ) {

                    // Try to delete the record because it couldn't pass the validation!
                    $print_date = $cd ? "am {$cd->getFullname()}" : "mit termin_id: {$scheduled_events['date_id']}";
                    $print_course = $course ? $course->name : "mit id: {$scheduled_events['seminar_id']}";
                    echo sprintf(
                        "Ungültiges geplantes Event, Löschen der Aufzeichnungsdaten %s für den Kurs %s\n",
                        $print_date, $print_course
                    );

                    $oc_event_id = $scheduled_events['event_id'];
                    $oc_config_id = $course_config_id;

                    // Delete the recording in OC.
                    if (!ScheduleHelper::validateCourseAndResource($scheduled_events['seminar_id'], $resource_obj['config_id'])) {
                        $oc_config_id = $resource_obj['config_id'];
                    }

                    $oc_set_index = array_search($oc_config_id, array_column($oc_scheduled_events, 'config_id'));
                    $oc_event_to_delete = null;

                    // search for the corresponding event in Opencast
                    if ($oc_set_index !== false && isset($oc_scheduled_events[$oc_set_index]['scheduled_events'][$oc_event_id])) {
                        $oc_event_to_delete = $oc_scheduled_events[$oc_set_index]['scheduled_events'][$oc_event_id];
                    }

                    if (!empty($oc_event_to_delete)) {
                        $scheduler_client = SchedulerClient::getInstance($oc_config_id);
                        $result = $scheduler_client->deleteEvent($oc_event_id);
                        if ($result) {
                            unset($oc_scheduled_events[$oc_set_index]['scheduled_events'][$oc_event_id]);
                        }
                    }

                    // Delete the recording in SOP.
                    ScheduledRecordings::unscheduleRecording($oc_event_id, $scheduled_events['resource_id'], $scheduled_events['date_id']);
                } else {
                    // If validation is passed, we try to update to the record on both sides.
                    // Update the record.
                    echo sprintf(
                        "Aktualisiere die Aufzeichnungsdaten am %s für den Kurs %s\n",
                        $cd->getFullname(), $course->name
                    );

                    $result = ScheduleHelper::updateEventForSeminar(
                        $scheduled_events['seminar_id'], $scheduled_events['date_id']
                    );

                    if ($result) {
                        $oc_set_index = array_search($course_config_id, array_column($oc_scheduled_events, 'config_id'));
                        $oc_event_id = $scheduled_events['event_id'];
                        if ($oc_set_index !== false && isset($oc_scheduled_events[$oc_set_index]['scheduled_events'][$oc_event_id])) {
                            unset($oc_scheduled_events[$oc_set_index]['scheduled_events'][$oc_event_id]);
                        }
                    } else {
                        // try to (re-)create event in opencast
                        echo 'Eintrag fehlt im Opencast, versuche ihn zu erstellen...';
                        $result = ScheduleHelper::scheduleEventForSeminar(
                            $scheduled_events['seminar_id'], $scheduled_events['date_id'],
                            $scheduled_event['is_livestream'] ? true : false
                        );

                        echo $result ? ' erfolgreich' : 'fehlgeschlagen';
                        echo "\n";
                    }
                }
            } catch (\Throwable $th) {
                echo sprintf(
                    "Error: seminar_id: %s, termin_id: %s\n message: %s\n",
                    $scheduled_events['seminar_id'], $scheduled_events['date_id'], $th->getMessage()
                );
                return false;
            }
        }

        // 4. Those scheduled events that are not stored in SOP, will appear in this block.
        // We try to delete them if the global config "OPENCAST_MANAGE_ALL_OC_EVENTS" is enabled!
        if (StudipConfig::get()->OPENCAST_MANAGE_ALL_OC_EVENTS) {
            echo _('Lösche nicht über Stud.IP Termine geplante Events:') . "\n";
        } else {
            echo _('Nicht über Stud.IP Termine geplante Events:') . "\n";
        }
        // Loop through the opencast scheduled events that are not yet proccessed above!
        // Each set is based on each opencast server config.
        foreach ($oc_scheduled_events as $oc_set) {
            if (!empty($oc_set['scheduled_events'])) {
                echo _('Opencast Server Config:') . " #{$oc_set['config_id']} \n";
                foreach ($oc_set['scheduled_events'] as $scheduled_event) {
                    echo $scheduled_event->identifier . ' ' . $scheduled_event->title . "\n";
                    if (StudipConfig::get()->OPENCAST_MANAGE_ALL_OC_EVENTS) {
                        $scheduler_client = SchedulerClient::getInstance($oc_set['config_id']);
                        $scheduler_client->deleteEvent($scheduled_event->identifier);
                    }
                }
            }
        }

        return true;
    }
}
