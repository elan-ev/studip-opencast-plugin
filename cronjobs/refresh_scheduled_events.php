<?php

require_once 'lib/classes/CronJob.class.php';
require_once __DIR__ .'/../classes/OCRestClient/SchedulerClient.php';

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
        return _('Aktualisiert alle geplanten Aufzeichnungen der in Stud.IP verbundenen Serien.');
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
        $stmt = DBManager::get()->prepare("SELECT * FROM oc_scheduled_recordings
                  LEFT JOIN oc_seminar_series USING (seminar_id)
                  WHERE oc_seminar_series.schedule=1");
        $stmt->execute(array());
        $scheduled_events  = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(!empty($scheduled_events)){
            foreach($scheduled_events as $se) {
                $scheduler_client = SchedulerClient::getInstance();
                $scheduler_client->updateEventForSeminar($se['seminar_id'], $se['resource_id'], $se['date_id'], $se['event_id']);
                $course = Course::find($se['seminar_id']);
                $date = new SingleDate($se['date_id']);
                echo sprintf(_("Aktualisieriere die Aufzeichnungsdaten für die Veranstaltung am %s für den Kurs %s\n "), $date->getDatesExport(), $course->name);
            }
        }

        return true;
    }
}