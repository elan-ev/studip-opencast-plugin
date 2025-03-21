<?php

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';

use Opencast\Models\Config;
use Opencast\Models\REST\Config as OCConfig;
use Opencast\Models\Videos;
use Opencast\Models\VideoSync;
use Opencast\Models\PlaylistVideos;
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
        $stmt_ids = $db->prepare("
            SELECT episode, version FROM oc_video
            WHERE config_id = :config_id AND available = 1
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

            // update endpoints, just to make sure
            // $config->updateEndpoints();

            // call opencast to get all event ids
            $api_client = ApiEventsClient::getInstance($config['id']);
            echo 'instantiated api_client' . "\n";

            $event_ids = [];
            $events = [];

            // get all known events in Stud.IP
            $stmt_ids->execute([':config_id' => $config['id']]);

            $local_events = $stmt_ids->fetchAll(PDO::FETCH_KEY_PAIR);
            $local_event_ids = array_keys($local_events);

            // paginated fetch of events from opencast
            $oc_events = [];
            $offset = 0;
            $limit  = 100;

            do {
                $paged_events = $api_client->getAll(['limit' => $limit, 'offset' => $offset]);
                $oc_events = array_merge($oc_events, $paged_events);

                $offset += $limit;
            } while (sizeof($paged_events) > 0);

            // load events from opencast filter the processed ones
            foreach ($oc_events as $event) {
                // only add videos / reinspect videos if they are readily processed
                if ($event->status == 'EVENTS.EVENTS.STATUS.PROCESSED') {
                    $event_ids[] = $event->identifier;
                    $events[$event->identifier] = $event;

                    // check archive versions and if they differ, reinspect the video
                    if (isset($local_events[$event->identifier])
                        && $local_events[$event->identifier] != $event->archive_version
                    ) {
                        // only add for reinspection if not already scheduled
                        $video = Videos::findOneBySql("episode = ?", [$event->identifier]);

                        if (empty(VideoSync::findByVideo_id($video->id))) {
                            echo 'schedule video for re-inspection, archive versions differ: ' . $video->id . ' (' . $video->title . ') '
                                . ' Local version: '. $local_events[$event->identifier] . ', OC version: '. $event->archive_version . "\n";

                            // create task to update permissions and everything else
                            $task = new VideoSync;

                            $task->setData([
                                'config_id' => $config['id'],
                                'video_id'  => $video->id,
                                'state'     => 'scheduled',
                                'scheduled' => date('Y-m-d H:i:s')
                            ]);

                            $task->store();
                        }
                    }
                } else if ($event->status != 'EVENTS.EVENTS.STATUS.SCHEDULED') {
                    // the event at least exists and is not scheduled
                    $event_ids[] = $event->identifier;
                    $events[$event->identifier] = $event;
                }
            }

            // Find new videos available in OC but not locally
            foreach (array_diff($event_ids, $local_event_ids) as $new_event_id) {
                echo 'found new video in Opencast #'. $config['id'] .': ' . $new_event_id . ' (' . $events[$new_event_id]->title . ")\n";

                $video = Videos::findOneBySql("episode = ?", [$new_event_id]);
                $is_livestream = (bool) $video->is_livestream ?? false;
                if (!$video) {
                    $video = new Videos;
                }
                $video->setData([
                    'episode'       => $new_event_id,
                    'config_id'     => $config['id'],
                    'title'         => $events[$new_event_id]->title,
                    'description'   => $events[$new_event_id]->description,
                    'duration'      => $events[$new_event_id]->duration,
                    'state'         => 'running',
                    'available'     => true,
                    'is_livestream' => $is_livestream
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

        // now check all videos which have no preview url and no scheduled task (these were not yet ready when whe inspected them)

        // TODO:
        /* current event state in oc_videos speichern
         * RUNNING wird immer neu inspiziert
         * FAILED wird nur jede Stunde neu inspiziert
         * Das scheduled Feld wird genutzt, um Dinge für die Zukunft zu planen
         */
        $videos = Videos::findBySql(
            "LEFT JOIN oc_video_sync AS ovs ON (ovs.video_id = oc_video.id AND ovs.type = 'video')
            WHERE ovs.video_id IS NULL AND (preview IS NULL OR available = 0) AND is_livestream = 0"
        );
        foreach ($videos as $video) {
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

        // search for all inaccessible videos in course playlists with no scheduled task and add them for reinspection
        $videos = PlaylistVideos::findBySql(
            "LEFT JOIN oc_video_sync AS ovs ON (ovs.video_id = oc_playlist_video.video_id AND ovs.type = 'playlistvideo')
            WHERE ovs.video_id IS NULL AND oc_playlist_video.available = 0"
        );
        foreach ($videos as $video) {
            echo 'schedule playlist video for re-inspection: ' . $video->video_id . ' ('. $video->video->title .', Playlist ID: ' . $video->playlist_id . ")\n";
            // create task to update permissions and everything else
            $task = new VideoSync;

            $task->setData([
                'video_id'  => $video->video_id,
                'state'     => 'scheduled',
                'type'      => 'playlistvideo',
                'scheduled' => date('Y-m-d H:i:s'),
                'data'   => json_encode([
                    'playlist_id' => $video->playlist_id,
                    'version'     => $video->video->version
                ])
            ]);

            $task->store();
        }

        // fix any broken playlists visibility
        DBManager::get()->exec("UPDATE oc_playlist_seminar SET visibility = 'visible'
            WHERE visibility IS NULL or visibility = ''");

    }

}
