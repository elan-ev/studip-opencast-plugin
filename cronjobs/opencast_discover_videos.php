<?php

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';

use Opencast\Models\Config;
use Opencast\Models\Videos;
use Opencast\Models\VideosArchive;
use Opencast\Models\VideoSync;
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
        $stmt_ids   = $db->prepare("SELECT episode FROM oc_video WHERE config_id = :config_id");

        // iterate over all configured oc instances
        $configs = Config::findBySql(1);


        foreach ($configs as $config) {
            echo 'working on config '. $config->id ."\n";
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

            foreach (array_diff($event_ids, $local_event_ids) as $new_event_id) {
                // check, if an entry for this episode_id exists in the archive and skip it if found
                $archive = VideosArchive::findOneByEpisode($new_event_id);

                if (empty($archive)) {
                    echo 'found new video in Opencast #'. $config['id'] .': ' . $new_event_id . ' (' . $events[$new_event_id]->title . ")\n";
                    $video = new Videos;

                    $video->setData([
                        'episode'     => $new_event_id,
                        'config_id'   => $config['id'],
                        'title'       => $events[$new_event_id]->title,
                        'description' => $events[$new_event_id]->description,
                        'duration'    => $events[$new_event_id]->duration
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
            }

            // hide all videos, which are not present in opencast anymore
            foreach (array_diff($local_event_ids, $event_ids) as $old_event_id) {
                $video = Videos::findOneByEpisode($old_event_id);

                if (!empty($video)) {
                    echo 'found video MISSING in Opencast #'. $config['id'] .': ' . $old_event_id . ' ('.  $video->title .")\n";

                    $archive = new VideosArchive();
                    $archive->setData($video->toArray());
                    try {
                        $archive->store();
                    } catch (PDOException $p) {
                        echo ' -> error during archiving: '. $p->getMessage() ."\n";
                    }

                    $video->delete();
                }
            }
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
