<?php

require_once __DIR__.'/../bootstrap.php';

use  Opencast\Models\OCScheduledRecordings;

class RefreshScheduledEvents extends CronJob
{

    /**
     * Return the name of the cronjob.
     */
    public static function getName()
    {
        return _('Opencast - "Scheduled-Events-Refresh"');
    }

    /**
     * Return the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Opencast: Aktualisiert alle geplanten Aufzeichnungen der in Stud.IP verbundenen Serien und löscht geplante Events, die nicht mehr benötigt werden.');
    }

    /**
     * Execute the cronjob.
     *
     * @param mixed $last_result What the last execution of this cronjob
     *                           returned.
     * @param Array $parameters Parameters for this cronjob instance which
     *                          were defined during scheduling.
     */
    public function execute($last_result, $parameters = array())
    {
        require_once __DIR__ .'/../classes/OCRestClient/SchedulerClient.php';

        echo "Lade geplante Events aus Opencast.\n";

        // TODO: consider multiple opencast installations
        // Opencast events are those reported as being scheduled by Opencast
        $api_client = ApiEventsClient::getInstance(1);
        $opencast_events = $api_client->getAllScheduledEvents($connected_events['series_id']);
        echo 'In Opencast geplante Events: ' . sizeof($opencast_events). "\n";

        // Only care about events created before now (i.e. avoids deletion
        // of events that should stay)
        $time = time();

        // Connected events are events that can be still be matched to
        // studip course dates (i.e. the date hasn't been removed from Stud.IP)
        // in the future
        $stmt = DBManager::get()->prepare("SELECT oc.*, oss.seminar_id, oss.series_id
            FROM oc_scheduled_recordings oc
            LEFT JOIN oc_seminar_series oss USING (seminar_id)
            JOIN termine t ON (termin_id = date_id)
            WHERE oss.schedule = 1
                AND t.date >= UNIX_TIMESTAMP()
                AND oc.mktime < :mktime");
        $stmt->execute([
            ':mktime' => $time
        ]);

        $connected_events  = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo 'Zu aktualisierende Events: ' . sizeof($connected_events) . "\n";

        // Curated events are all future events that Stud.IP scheduled
        $stmt = DBManager::get()->prepare("SELECT event_id
            FROM oc_scheduled_recordings
            WHERE start >= UNIX_TIMESTAMP()
                AND mktime < :mktime");
        $stmt->execute([
            ':mktime' => $time
        ]);

        $curated_events  = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $curated_events = array_flip($curated_events);

        echo 'In Stud.IP geplante Events ohne Zuordnung: ' .
            (sizeof($curated_events) - sizeof($connected_events)) . "\n";

        if (!empty($connected_events)) {
            foreach ($connected_events as $se) {
                $cd = CourseDate::find($se['date_id']);
                $course = Course::find($se['seminar_id']);

                if ($cd) {
                    unset($opencast_events[$se['event_id']]);
                    unset($curated_events[$se['event_id']]);

                    if ($cd->room_booking->resource_id == $se['resource_id']) {
                        $scheduler_client = SchedulerClient::create($se['seminar_id']);
                        $scheduler_client->updateEventForSeminar($se['seminar_id'], $se['resource_id'], $se['date_id'], $se['event_id']);

                        echo sprintf(
                            _("Aktualisiere die Aufzeichnungsdaten am %s für den Kurs %s\n"),
                            $cd->getFullname(), $course->name
                        );
                  } else {
                      echo sprintf(
                          _("Abweichender Raum, Löschen der Aufzeichnungsdaten am %s für den Kurs %s\n"),
                          $cd->getFullname(), $course->name
                      );

                      $scheduler_client = SchedulerClient::getInstance(1);
                      $scheduler_client->deleteEvent($se['event_id']);

                      OCScheduledRecordings::deleteBySql('event_id = ?', [$se['event_id']]);
                 }
              }
           }
        }

        // Delete remaining events that have been scheduled through Stud.IP but
        // have no course date anymore
        echo _('Lösche von Stud.IP geplante Events mit fehlenden Veranstaltungsterminen:') . "\n";
        foreach ($curated_events as $event_id => $dummy) {
            // Check if the event exists as SCHEDULED event in Opencast (i.e.
            // excluding events with videos)
            if (isset($opencast_events[$event_id])) {
                echo $event_id . ' ' . $opencast_events[$event_id]->title . "\n";

                unset($opencast_events[$event_id]);

                $scheduler_client = SchedulerClient::getInstance(1);
                $scheduler_client->deleteEvent($event_id);
                OCScheduledRecordings::deleteBySql('event_id = ?', [$event_id]);
           }
        }

        // remaining events have no association to Stud.IP
        if (Config::get()->OPENCAST_MANAGE_ALL_OC_EVENTS) {
            echo _('Lösche nicht über Stud.IP Termine geplante Events:') . "\n";
        } else {
            echo _('Nicht über Stud.IP Termine geplante Events:') . "\n";
        }
        if (is_array($opencast_events)) foreach ($opencast_events as $event) {
            echo $event->identifier . ' ' . $event->title . "\n";

            if (Config::get()->OPENCAST_MANAGE_ALL_OC_EVENTS) {
                $scheduler_client = SchedulerClient::getInstance(1);
                $scheduler_client->deleteEvent($event->identifier);
                OCScheduledRecordings::deleteBySql('event_id = ?', [$event->identifier]);
            }
        }

        return true;
    }
}
