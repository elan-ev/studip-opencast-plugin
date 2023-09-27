<?php
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';


use Opencast\Models\Videos;

class OpencastClearRecycleBin extends CronJob
{

    public static function getName()
    {
        return _('Opencast - Zum löschen markierte Videos endgültig löschen');
    }

    public static function getDescription()
    {
        return _('Opencast: Löscht die zur endgültigen Löschung vorgesehenen Videos aus den gelöschten Videos der Nutzer.');
    }

    public function execute($last_result, $parameters = array())
    {
        echo "Deletes all videos that have been marked as trash for at least " . \Config::get()->OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL . " days\n";

        $videos = Videos::findBySql("trashed=true AND state!='running' AND trashed_timestamp < NOW() - INTERVAL " . \Config::get()->OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL ." DAY");
        foreach ($videos as $video) {
            echo "Video #" . $video->id . " (" . $video->title . ") ";
            if ($video->removeVideo()) {
                echo "removed\n";
            }
            else {
                echo "remove failed\n";
            }
        }
    }

}
