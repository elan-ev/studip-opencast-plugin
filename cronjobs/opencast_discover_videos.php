<?php

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';

use Opencast\Models\Config;
use Opencast\Models\REST\Config as OCConfig;
use Opencast\Models\Videos;
use Opencast\Models\VideoSync;
use Opencast\Models\WorkflowConfig;
use Opencast\Models\REST\ApiEventsClient;

class OpencastDiscoverVideos extends CronJob
{

    public static function getName()
    {
        return _('Opencast - Neue Videos finden');
    }

    public static function getDescription()
    {
        return _('Opencast: Katalogisiert neue Videos aus Opencast.');
    }

    public function execute($last_result, $parameters = array())
    {
        /*
         - Neue Videos in OC identifizieren (die Stud.IP noch nicht kennt)
         - Eintragen der Videos und setzen der Rechte (Queue)
        */
        $db = DBManager::get();
        $stmt_ids   = $db->prepare("
            SELECT episode FROM oc_video WHERE config_id = :config_id AND available=true
            ");

        // iterate over all active configured oc instances
        $configs = Config::findBySql('active = 1');


        foreach ($configs as $config) {
            // check, if this opencast instance is accessible
            $version = false;

            echo 'working on config '. $config->id ."\n";
            $version = OCConfig::getOCBaseVersion($config->id);

            if (!$version) {
                echo 'cannot connect to opencast, skipping!' ."\n";
                continue;
            } else {
                echo "found opencast with version $version, continuing\n";
            }

            // call opencast to get all event ids
            $api_client = ApiEventsClient::getInstance($config['id']);
            echo 'instantiated api_client' . "\n";

            $event_ids = [];
            $events = [];

            foreach ($api_client->getAll() as $event) {
                // only add videos / reinspect videos if they are readily processed
                if ($event->status == 'EVENTS.EVENTS.STATUS.PROCESSED') {
                    $event_ids[] = $event->identifier;
                    $events[$event->identifier] = $event;
                }
            }

            // check if these event_ids have a corresponding entry in Stud.IP

            $stmt_ids->execute([':config_id' => $config['id']]);

            $local_event_ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);
            //echo 'found oc events:' . "\n";
            //print_r($events);

            //echo 'found local events:' . "\n";
            //print_r($local_event_ids);

            // Find new videos available in OC but not locally
            foreach (array_diff($event_ids, $local_event_ids) as $new_event_id) {
                echo 'found new video in Opencast #'. $config['id'] .': ' . $new_event_id . ' (' . $events[$new_event_id]->title . ")\n";

                $video = Videos::findOneBySql("config_id = ? AND episode = ?", [$config['id'], $new_event_id]);
                if (!$video) {
                    $video = new Videos;
                }
                $video->setData([
                    'episode'     => $new_event_id,
                    'config_id'   => $config['id'],
                    'title'       => $events[$new_event_id]->title,
                    'description' => $events[$new_event_id]->description,
                    'duration'    => $events[$new_event_id]->duration,
                    'state'       => 'running',
                    'available'   => true
                ]);
                $video->store();

                // create task to update permissions and everything else
                $task = new VideoSync;

                $task->setData([
                    'video_id'  => $video->id,
                    'state'     => 'scheduled',
                    'scheduled' => date('Y-m-d H:i:s')
                ]);

                $task->store();
            }

            // Check if local videos are not longer available in OC
            foreach (array_diff($local_event_ids, $event_ids) as $event_id) {
                $video = Videos::findOneBySql("config_id = ? AND episode = ?", [$config['id'], $event_id]);
                // Need null check for archived videos
                if ($video) {
                    $video->setValue('available', false);
                    $video->store();
                }
            }

            // Update Workflows
            WorkflowConfig::createAndUpdateByConfigId($config['id']);
        }

        // now check all videos which have no preview url (these were not yet ready when whe inspected them)

        // TODO:
        /* current event state in oc_videos speichern
         * RUNNING wird immer neu inspiziert
         * FAILED wird nur jede Stunde neu inspiziert
         * Das scheduled Feld wird genutzt, um Dinge für die Zukunft zu planen
         */
        foreach (Videos::findBySql('preview IS NULL') as $video) {
            // check, if there is already a task scheduled
            if (empty(VideoSync::findByVideo_id($video->id))) {
                echo 'schedule video for re-inspection: ' . $video->id . ' (' . $video->title . ")\n";
                // create task to update permissions and everything else
                $task = new VideoSync;

                $task->setData([
                    'video_id'  => $video->id,
                    'state'     => 'scheduled',
                    'scheduled' => date('Y-m-d H:i:s')
                ]);

                $task->store();
            }
        }

    }

}
