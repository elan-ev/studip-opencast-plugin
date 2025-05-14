<?php
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';

use Opencast\Models\Config;
use Opencast\Models\Videos;
use Opencast\Models\REST\ApiEventsClient;
use Opencast\Models\REST\Config as OCConfig;

class OpencastSyncAcls extends CronJob
{

    public static function getName()
    {
        return _('Opencast - Synchronisiert ACLs für Events');
    }

    public static function getDescription()
    {
        return _('Opencast: Synchronisiert ACLs für Events');
    }

    /**
     * Iterate over all videos andf playlists from opencast known to Stud.IP
     * and set the correct ACLs accordingly
     *
     * @param string $last_result
     * @param array $parameters
     *
     * @return void
     */
    public function execute($last_result, $parameters = array())
    {
        $db = DBManager::get();

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

            // paginated fetch of events from opencast
            $offset = 0;
            $limit  = 100;

            do {
                $oc_events = $api_client->getAll([
                    'limit'   => $limit,
                    'offset'  => $offset,
                    'sort'    => 'date:DESC',
                    'withacl' => 'true'
                ]);

                $offset += $limit;

                if (!empty($oc_events)) foreach ($oc_events as $event) {
                    // only add videos / reinspect videos if they are readily processed
                    if ($event->status == 'EVENTS.EVENTS.STATUS.PROCESSED') {
                        // check if video exists in Stud.IP
                        $video = Videos::findByEpisode($event->identifier);

                        $video->created = date('Y-m-d H:i:s', strtotime($event->created));
                        $video->store();

                        if ($video->config_id != $config->id) {
                            echo 'config id mismatch for Video with id: '. $video->id .", $config->id <> {$video->config_id}\n";
                            continue;
                        }

                        Videos::checkEventACL(null, $event, $video);
                    }
                }
            } while (sizeof($oc_events) > 0);
        }
    }

}
