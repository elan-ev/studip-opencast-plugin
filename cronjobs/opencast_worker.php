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
        // create 10 threads (if possible) doing the work to speed things up

        $start_time = time();
        // get next task and run it
        // if a minute has already passed, stop executing tasks and finish the cronjob

        while ($start_time > (time() - 59)
            && !empty($task = VideoSync::findOneBySQL("state = 'scheduled'
                WHERE scheduled <= NOW()
                ORDER BY scheduled ASC", []))
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

                $video->title       = $event->title;
                $video->description = $event->description;
                $video->duration    = $event->duration;
                $video->publication = json_encode($event->publications);

                if (!$video->token) {
                    $video->token = bin2hex(random_bytes(8));   // TODO: How to connect this with Providers/Tokens?
                }

                $video->store();

                // send out Notification for video discovery plugins to react
                NotificationCenter::postNotification('OpencastVideoSync', $event, $video);
            }

            $task->delete();
        }
    }

}
