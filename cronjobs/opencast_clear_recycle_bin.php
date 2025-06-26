<?php
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';


use Opencast\Models\Videos;
use Opencast\Models\REST\ApiEventsClient;
use Opencast\Helpers\CronjobUtils\OpencastConnectionCheckerTrait;

class OpencastClearRecycleBin extends CronJob
{

    use OpencastConnectionCheckerTrait;

    public static function getName()
    {
        return _('Opencast - Zum Löschen markierte Videos endgültig löschen');
    }

    public static function getDescription()
    {
        return _('Opencast: Löscht die zur endgültigen Löschung vorgesehenen Videos aus den gelöschten Videos der Nutzer.');
    }

    public function execute($last_result, $parameters = array())
    {
        echo "Deletes all videos that have been marked as trash for at least " . \Config::get()->OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL . " days\n";

        $videos = Videos::findBySql($q = "trashed = 1 AND (
                state != 'running' OR
                state IS NULL
            ) AND trashed_timestamp
                < NOW() - INTERVAL " . \Config::get()->OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL ." DAY");

        // We need to group the videos based on the config_id, in order to check the connection first.
        $videos_by_config = [];
        foreach ($videos as $video) {
            $config_id = $video->config_id ?? \Config::get()->OPENCAST_DEFAULT_SERVER;
            if (!isset($videos_by_config[$config_id])) {
                $videos_by_config[$config_id] = [];
            }
            $videos_by_config[$config_id][] = $video;
        }

        // Now, we loop through the grouped videos.
        foreach ($videos_by_config as $config_id => $videos) {
            echo 'Working on config with id #' . $config_id . "\n";

            // Checking the connection and maintenance status.
            if (!$this->isOpencastReachable($config_id) || $this->isOpencastUnderMaintenance($config_id)) {
                continue;
            }

            // To increase performance we instantiate the ApiEndpoint once in here!
            $api_event_client = ApiEventsClient::getInstance($config_id);

            // Deleting videos.
            foreach ($videos as $video) {
                echo "Video #" . $video->id . " (" . $video->title . ") ";
                if ($video->removeVideo($api_event_client)) {
                    echo "removed\n";
                }
                else {
                    echo "remove failed\n";
                }
            }
        }
    }

}
