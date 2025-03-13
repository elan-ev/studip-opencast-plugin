<?php
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';


use Opencast\Models\VideoSync;
use Opencast\Models\Videos;
use Opencast\Models\ScheduleHelper;
use Opencast\Models\PlaylistVideos;
use Opencast\Models\REST\ApiEventsClient;

class OpencastWorker extends CronJob
{

    public static function getName()
    {
        return _('Opencast - Queue');
    }

    public static function getDescription()
    {
        return _('Opencast: Arbeitet vorgemerkte Aufgaben ab, wie Aktualisierung der Metadaten, ACLs (Sichtbarkeit), etc.');
    }

    public function execute($last_result, $parameters = array())
    {
        return;
        $start_time = time();
        // get next task and run it
        // if 5 minutes have already passed, stop executing tasks and finish the cronjob

        while ($start_time > (time() - 299)
            && !empty($task = VideoSync::findOneBySQL("
                    scheduled <= NOW() AND state = 'scheduled'
                    ORDER BY scheduled ASC",
                []))
        ) {
            $task->state = 'running';
            $task->trys++;
            $task->store();

            // check acls, metadata and permissions

            $video = Videos::find($task->video_id);

            // always make sure the video token is set
            if (!$video->token) {
                $video->token = bin2hex(random_bytes(6));
                $video->store();
            }

            if (!empty($video)) {
                echo 'updating video: ' . $video->id . "\n";

                try {
                    $is_livestream = (bool) $video->is_livestream ?? false;
                    if ($is_livestream) {
                        echo 'this video is a livestream event' . "\n";
                    }

                    if (empty($video->config_id)) {
                        echo 'No config_id for video '. $video->id. "\n";
                        $task->state = 'failed';
                        $task->data = json_encode(['error' => 'No config_id for video '. $video->id]);
                        $task->store();
                        continue;
                    }

                    $api_client = ApiEventsClient::getInstance($video->config_id);
                    $params = [
                        'withpublications' => true
                    ];
                    if ($is_livestream) {
                        $params['withscheduling'] = true;
                    }

                    if (empty($video->episode)) {
                        echo 'No episode_id for video '. $video->id. "\n";
                        $task->delete();
                        continue;
                    }

                    $event = $api_client->getEpisode($video->episode, $params);

                    if ($event) {
                        if ($task->type == 'video') {
                            if (in_array($event->processing_state, ['FAILED', 'STOPPED', '']) === true) { // It failed.
                                if ($video->state != 'failed') {
                                    $video->state = 'failed';
                                }
                            } else if ($event->status === "EVENTS.EVENTS.STATUS.PROCESSED" && $event->has_previews == true
                            && count($event->publication_status) == 1 && $event->publication_status[0] == "internal") {
                                if ($video->state != 'cutting') {
                                    $video->state = 'cutting';
                                    NotificationCenter::postNotification('OpencastNotifyUsers', $event, $video);
                                }
                            } else if ($event->status === "EVENTS.EVENTS.STATUS.SCHEDULED" || $event->status === "EVENTS.EVENTS.STATUS.RECORDING") { // Is scheduled or live
                                $video->state = 'running';
                                $video->is_livestream = 1;
                                if (!empty($event->publications)) {
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
                                if ($is_livestream && !empty($event->scheduling)) {
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

                            $video->title        = $event->title;
                            $video->description  = $event->description;
                            $video->duration     = $event->duration;

                            $video->created      = date('Y-m-d H:i:s', strtotime($event->created));
                            $video->presenters   = implode(', ', (array)$event->presenter);
                            $video->contributors = implode(', ', (array)$event->contributor);

                            echo 'storing video '. $video->id. " to database... \n";
                            $video->store();

                            // task is done, delete it
                            $task->delete();
                        } else {
                            echo 'checking playlist video: '. $video->id. "\n";
                            $data = json_decode($task->data, true);

                            $pvideo = PlaylistVideos::findOneBySQL(
                                'playlist_id = ? AND video_id = ?',
                                [$data['playlist_id'], $video->id]
                            );

                            if ($pvideo && $event->processing_state != "RUNNING"
                                && $data['version'] != $event->archive_version
                            ) {
                                echo 'video '. $video->id .' is now available' . "\n";
                                $pvideo->available = 1;
                                $pvideo->store();
                                $task->delete();
                            } else {
                                $task->state = 'scheduled';
                                $task->scheduled = date('Y-m-d H:i:s', strtotime('+'. ($task->trys * 3) .' minutes'));
                                $task->store();
                            }
                        }

                        // send out Notifications for video discovery plugins to react
                        NotificationCenter::postNotification('OpencastCourseSync', $event, $video);
                        NotificationCenter::postNotification('OpencastVideoSync', $event, $video);
                    } else {
                        echo 'could not find event for video '. $video->id.  ' in opencast, tried '. $task->trys ." times.\n";
                        // event is missing or has no publications.
                        if ($task->trys >= 10) {
                            // if we tried 10 times, we give up, event seems to be missing/broken in opencast!
                            echo 'giving up on video '. $video->id. "\n";

                            $task->state = 'failed';
                            $task->store();
                        } else {
                            // reschedule task to be run again in 3 minutes
                            $task->state = 'scheduled';
                            $task->scheduled = date('Y-m-d H:i:s', strtotime('+'. ($task->trys * 3) .' minutes'));
                            $task->store();
                        }

                        //$video->delete();
                    }
                } catch (\Exception $e) {
                    echo 'Error updating video '. $video->id .': '. $e->getMessage(). "\n";
                    continue;
                }
            }

            // something went wrong, try again next time
            if ($task->state == 'running') {
                $task->state = 'scheduled';
                $task->store();
            }

        }
    }

}
