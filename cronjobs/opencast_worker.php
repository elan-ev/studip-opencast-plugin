<?php
require_once __DIR__.'/../bootstrap.php';

use Opencast\Models\VideoSync;
use Opencast\Models\Videos;
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
        $start_time = time();
        // get next task and run it
        // if a minute has already passed, stop executing tasks and finish the cronjob

        while ($start_time > (time() - 59)
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

            if (!empty($video)) {
                echo 'updating video: ' . $video->id . "\n";
                $api_client = ApiEventsClient::getInstance($video->config_id);
                $event = $api_client->getEpisode($video->episode, ['withpublications' => 'true']);

                if ($event && !empty($event->publications)) {
                    $video->title       = $event->title;
                    $video->description = $event->description;
                    $video->duration    = $event->duration;
                    $video->publication = json_encode($event->publications);

                    if (!$video->token) {
                        $video->token = bin2hex(random_bytes(8));   // TODO: How to connect this with Providers/Tokens?
                    }

                    $video->store();

                    // task is done, delete it
                    $task->delete();

                    // send out Notification for video discovery plugins to react
                    NotificationCenter::postNotification('OpencastVideoSync', $event, $video);
                } else {
                    // event is missing or has no publications.
                    if ($task->trys >= 10) {
                        // if we tried 10 times, we give up, event seems to be missing/broken in opencast!
                        $task->state = 'failed';
                        $task->store();
                    } else {
                        // reschedule task to be run again in 3 minutes
                        $task->state = 'scheduled';
                        $task->scheduled = date('Y-m-d H:i:s', strtotime('+3 minutes'));
                        $task->store();
                    }

                    //$video->delete();
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
