<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Opencast\Models\Config;
use Opencast\Models\Playlists;
use Opencast\Models\REST\ApiPlaylistsClient;
use Opencast\Models\REST\Config as OCConfig;

class OpencastSyncPlaylists extends CronJob
{
    public static function getName()
    {
        return _('Opencast - Wiedergabelisten synchronisieren');
    }

    public static function getDescription()
    {
        return _('Opencast: Synchronisiert Wiedergabelisten aus Opencast mit allen Wiedergabelisten in Stud.IP, indem neue Wiedergabelisten erstellt, bestehende Wiedergabelisten aktualisiert und nicht vorhandene Wiedergabelisten entfernt werden.');
    }

    public function execute($last_result, $parameters = array())
    {
        $db = DBManager::get();
        $stmt_ids = $db->prepare("
            SELECT service_playlist_id FROM oc_playlist WHERE config_id = :config_id
            ");

        // iterate over all active configured oc instances
        $configs = Config::findBySql('active = 1');

        foreach ($configs as $config) {
            // check, if this opencast instance is accessible
            $version = false;

            echo 'working on config ' . $config->id . "\n";
            $version = OCConfig::getOCBaseVersion($config->id);

            if (!$version) {
                echo 'cannot connect to opencast, skipping!' . "\n";
                continue;
            } else {
                echo "found opencast with version $version, continuing\n";
            }

            // call opencast to get all playlists
            $api_client = ApiPlaylistsClient::getInstance($config['id']);
            echo 'instantiated api_client' . "\n";

            $playlists = [];
            $playlist_ids = [];

            $api_client->getAll();

            // Assume the admin user has access to all playlists
            foreach ($api_client->getAll() as $playlist) {
                $playlist_ids[] = $playlist->id;
                $playlists[$playlist->id] = $playlist;
            }

            // check if these playlist_ids have a corresponding playlist in Stud.IP

            $stmt_ids->execute([':config_id' => $config['id']]);

            $local_playlist_ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);

            // Update existing playlists
            foreach ($playlist_ids as $playlist_id) {
                $playlist = Playlists::findOneBySQL(
                    'config_id = ? AND service_playlist_id = ?',
                    [$config['id'], $playlist_id]
                );

                if (is_null($playlist)) {
                    // Ignore unknown playlists possibly created externally
                    continue;
                }

                echo 'update playlist of Opencast #'. $config['id'] .': ' . $playlist_id . ' (' . $playlists[$playlist_id]->title . ")\n";

                // Update playlist data
                $playlist->setData([
                    'config_id' => $config['id'],
                    'service_playlist_id' => $playlist_id,
                    'title' => $playlists[$playlist_id]->title,
                    'description' => $playlists[$playlist_id]->description,
                    'creator' => $playlists[$playlist_id]->creator,
                    'updated' => date('Y-m-d H:i:s', strtotime($playlists[$playlist_id]->updated)),
                ]);
                $playlist->store();

                // Update playlist entries
                $playlist->setEntries($playlists[$playlist_id]->entries);

                // Check ACLs for playlist
                Playlists::checkPlaylistACL($playlists[$playlist_id], $playlist);
            }

            // Check if local playlists are no longer available in OC
            foreach (array_diff($local_playlist_ids, $playlist_ids) as $playlist_id) {
                echo 'delete non-existent playlist in Opencast #'. $config['id'] .': ' . $playlist_id . ' (' . $playlists[$playlist_id]->title . ")\n";
                $playlist = Playlists::findOneBySQL(
                    'config_id = ? AND service_playlist_id = ?',
                    [$config['id'], $playlist_id]
                );

                // Need null check for archived playlists
                if ($playlist) {
                    $playlist->delete();
                }
            }
        }
    }
}