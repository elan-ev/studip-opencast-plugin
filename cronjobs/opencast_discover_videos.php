<?php

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';

use Opencast\Models\Config;
use Opencast\Models\REST\Config as OCConfig;
use Opencast\Models\Videos;
use Opencast\Models\PlaylistVideos;
use Opencast\Models\WorkflowConfig;
use Opencast\Models\REST\ApiEventsClient;
use Opencast\Models\Helpers;

class OpencastDiscoverVideos extends CronJob
{

    /**
     * @var array The required properties of the event object which will be checked before any operation.
     */
    private static $required_event_properties = [
        'archive_version'
    ];

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

            echo 'Working on config with id #'. $config->id ."\n";
            $version = OCConfig::getOCBaseVersion($config->id);

            if (!$version) {
                echo 'Cannot connect to opencast, skipping!' ."\n";
                continue;
            } else {
                echo "Found opencast with version $version, continuing\n";
            }

            // update endpoints, just to make sure
            // $config->updateEndpoints();

            // call opencast to get all event ids
            $api_client = ApiEventsClient::getInstance($config['id']);
            echo 'Instantiated ApiEventsClient' . "\n";

            $event_ids = [];

            // get all known events in Stud.IP
            $stmt_ids->execute([':config_id' => $config['id']]);

            $local_events = $stmt_ids->fetchAll(PDO::FETCH_KEY_PAIR);
            $local_event_ids = array_keys($local_events);

            // paginated fetch of events from opencast
            $oc_events = [];
            $offset = 0;
            $limit  = 100;

            // search for all inaccessible videos in course playlists with no scheduled task and add them for reinspection
            $videos = PlaylistVideos::findBySql('oc_playlist_video.available = 0');
            $playlist_videos = [];

            foreach ($videos as $video) {
                $playlist_videos[$video->video->episode] = $video;
            }

            echo 'Loading videos from Opencast and checking for new or updated ones...' . "\n";
            do {
                $oc_events = $api_client->getAll([
                    'limit'            => $limit,
                    'offset'           => $offset,
                    'withpublications' => 'true',
                    'withacl'          => 'true'
                ]);
                $offset += $limit;

                // load events from opencast and filter the processed ones
                if (!empty($oc_events)) foreach ($oc_events as $event) {
                    $current_event = null;

                    // Check the required properties of the event.
                    if (!self::checkEventIntegrity($event)) {
                        echo '[Skipped] The event does not have all required properties, skipping: ' . $event->identifier . "\n";
                        continue;
                    }

                    // Is the episode related to this studip?
                    // We need to check this, because it might happen that the Opencast server is connected to multiple Stud.IP instances,
                    // and we only want to process events that are related to this Stud.IP instance.
                    if (!Helpers::isEventInThisStudip($event)) {
                        echo '[Skipped] Event not related to this Stud.IP instance, skipping: ' . $event->identifier . "\n";
                        continue;
                    }

                    // only add videos / reinspect videos if they are readily processed
                    if ($event->status == 'EVENTS.EVENTS.STATUS.PROCESSED') {
                        $event_ids[] = $event->identifier;
                        $current_event = $event;

                        // check archive versions and if they differ, reinspect the video
                        if (isset($local_events[$event->identifier])
                            && $local_events[$event->identifier] != $event->archive_version
                        ) {
                            $video = Videos::findOneBySql("episode = ?", [$event->identifier]);

                            if (!empty($video)) {
                                echo 'Reinspect video, archive versions differ: ' . $video->id . ' (' . $video->title . ') '
                                        . ' Local version: '. $local_events[$event->identifier] . ', OC version: '. $event->archive_version . "\n";

                                self::parseEvent($event, $video);

                                // remove video from checklist for playlist videos (if even present)
                                unset($playlist_videos[$current_event->identifier]);
                            }
                        }

                    } else if ($event->status != 'EVENTS.EVENTS.STATUS.SCHEDULED') {
                        // the event at least exists and is not scheduled
                        $event_ids[] = $event->identifier;
                        $current_event = $event;
                    }

                    // add video if it is unknown to Stud.IP
                    if (!empty($current_event) && !isset($local_events[$current_event->identifier])) {
                        echo 'found new video in Opencast #'. $config['id'] .': ' . $current_event->identifier . ' (' . $current_event->title . ")\n";

                        $video = Videos::findOneBySql("episode = ?", [$current_event->identifier]);
                        $is_livestream = (bool) $video->is_livestream ?? false;

                        if (!$video) {
                            $video = new Videos;
                        }

                        $video->setData([
                            'episode'       => $current_event->identifier,
                            'config_id'     => $config['id'],
                            'title'         => $current_event->title,
                            'description'   => $current_event->description,
                            'duration'      => $current_event->duration,
                            'state'         => 'running',
                            'available'     => true,
                            'is_livestream' => $is_livestream
                        ]);
                        $video->store();

                        self::parseEvent($current_event, $video);

                        // remove video from checklist for playlist videos (if even present)
                        unset($playlist_videos[$current_event->identifier]);
                    }

                    // make sure that recording failures are handled
                    if (!empty($current_event)
                        && $current_event->status == 'EVENTS.EVENTS.STATUS.RECORDING_FAILURE'
                        && isset($local_events[$current_event->identifier])
                    ) {
                        $video = Videos::findOneBySql("episode = ?", [$current_event->identifier]);

                        if (!empty($video)) {
                            $video->state = 'failed';
                            $video->store();
                        }
                    }


                    // check if this event has a corresponding playlist entry and still needs reinspection
                    if (!empty($current_event) && isset($playlist_videos[$current_event->identifier])) {
                        $plvideo = $playlist_videos[$current_event->identifier];
                        echo 'Reinspect playlist video, if it is available now: ' . $plvideo->video_id . ' ('. $plvideo->video->title .', Playlist ID: ' . $plvideo->playlist_id . ")\n";
                        self::parseEvent($current_event, $plvideo->video);
                        $plvideo->available = 1;
                        $plvideo->store();
                    }
                }
            } while (!empty($oc_events));

            // Check if local videos are not longer available in OC and remove them from Stud.IP
            // If the video should reappear in the future, Stud.IP will readd it again.
            foreach (array_diff($local_event_ids, $event_ids) as $event_id) {
                $video = Videos::findOneBySql("config_id = ? AND episode = ?", [$config['id'], $event_id]);
                // Need null check for archived videos
                if ($video) {
                    $video->delete();
                }
            }

            // Update Workflows
            WorkflowConfig::createAndUpdateByConfigId($config['id']);
        }

        // fix any broken playlists visibility
        DBManager::get()->exec("UPDATE oc_playlist_seminar SET visibility = 'visible'
            WHERE visibility IS NULL or visibility = ''");

        echo 'Finished updating videos and workflows' . "\n";
    }

    /**
     * Check if the event has all required properties.
     *
     * @param object $event The event object to check.
     * @return bool True if the event has all required properties, false otherwise.
     */
    private static function checkEventIntegrity($event) {
        // Check if the event has all required properties
        foreach (self::$required_event_properties as $property) {
            if (!property_exists($event, $property)) {
                return false;
            }
        }

        return true;
    }

    private static function parseEvent($event, $video)
    {
        if (in_array($event->processing_state, ['FAILED', 'STOPPED']) === true) { // It failed.
            if ($video->state != 'failed') {
                $video->version = $event->archive_version;
                $video->state = 'failed';
            }
        } else if ($event->status === "EVENTS.EVENTS.STATUS.PROCESSED" && $event->has_previews == true
        && count($event->publication_status) == 1 && $event->publication_status[0] == "internal") {
            if ($video->state != 'cutting') {
                $video->state = 'cutting';
                NotificationCenter::postNotification('OpencastNotifyUsers', $event, $video);
            }
        } else if ($event->status === "EVENTS.EVENTS.STATUS.RECORDING") { // Is currently recording
            $video->state = 'running';
            if (!empty($event->publications)) {
                $video->is_livestream = 1;
                $video->publication = json_encode($event->publications);
            }
        } else if ($event->status === "EVENTS.EVENTS.STATUS.INGESTING" ||
            $event->status === "EVENTS.EVENTS.STATUS.PENDING") {
            $video->state = 'running';
        } else if ($event->status === "EVENTS.EVENTS.STATUS.PROCESSED" && !empty($event->publications)) {
            $video->publication = json_encode($event->publications);
            $video->state = null;
            $video->available = 1;
            $video->version   = $event->archive_version;
            $video->is_livestream = 0;

            // TODO: only notify for successful publication events. Currently no easily possible,
            // but an Opencast webhook will be facilitated in the near future.
            // NotificationCenter::postNotification('OpencastNotifyUsers', $event, $video);

        } else if ($event->status === "EVENTS.EVENTS.STATUS.PROCESSED" && empty($event->publications)) {
            if ($video->is_livestream && !empty($event->scheduling)) {
                $start = strtotime($event->scheduling->start);
                $end = strtotime($event->scheduling->end);
                $livestream_status = ScheduleHelper::getLivestreamTimeStatus($start, $end);
                if ($livestream_status == ScheduleHelper::LIVESTREAM_STATUS_FINISHED) { // Livestream is finished.
                    // getting video out of livestream
                    $video->is_livestream = 0;
                    // set the state as running, so that it will be picked up at the next run!
                    $video->state = 'running';
                    if (!empty($video->publication)) {
                        $video->publication = null;
                    }
                }
            } else {
                $video->state = 'failed';
                $video->version   = $event->archive_version;
                $video->is_livestream = 0;
            }
        }

        if (!$video->token) {
            $video->token = bin2hex(random_bytes(6));
        }

        $video->title        = $event->title;
        $video->description  = $event->description;
        $video->duration     = $event->duration;

        $video->created      = date('Y-m-d H:i:s', strtotime($event->created));
        $video->presenters   = implode(', ', (array)$event->presenter);
        $video->contributors = implode(', ', (array)$event->contributor);

        echo 'storing video '. $video->id. " to database... \n";
        $video->store();

        // send out Notifications for video discovery plugins to react
        NotificationCenter::postNotification('OpencastCourseSync', $event, $video);
        NotificationCenter::postNotification('OpencastVideoSync', $event, $video);
    }
}
