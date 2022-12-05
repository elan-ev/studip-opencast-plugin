<?php

require_once(__DIR__ . '/../bootstrap.php');

use Opencast\Models\Videos;
use Opencast\Models\VideoSync;

class MigrateExistingEpisodes extends Migration
{
     public function description()
    {
        return 'Migrate all entries from oc_seminar_episodes to oc_video and schedule ACL updates';
    }

    public function up()
    {
        set_time_limit(3600);

        $db = DBManager::get();

        $stmt_sem = $db->prepare("INSERT INTO oc_video_seminar
            (video_id, seminar_id, visibility)
            VALUES (:video_id, :seminar_id, :vis)
        ");

        $results = $db->query("SELECT DISTINCT episode_id, oss.config_id, visible
            FROM oc_seminar_episodes
            LEFT JOIN oc_seminar_series AS oss USING (series_id)
            WHERE 1
        ");

        $db->exec('START TRANSACTION');

        // first off, find all distinct episodes and add them to oc_videos
        while($data = $results->fetch())
        {
            $video = Videos::findOneByEpisode($data['episode_id']);

            if (empty($video)) {
                $video = new Videos();
                $video->setData([
                    'episode'     => $data['episode_id'],
                    'config_id'   => $data['config_id'],
                    'visibility'  => $data['visible'] == 'free' ? 'public' : 'internal'                 // if the episode has been world visible, keep it world visible (in the old plugin,
                                                                                                        // the episodes were world visible in any connected seminar as well, so this should work).
                                                                                                        // Otherwise we keep it as 'internal', because seminar visibility is handled in the second migration step
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

        $db->exec('COMMIT');

        // second, set seminar related permissions for every single entry

        $results = $db->query("SELECT ose.*, oss.config_id,
            oss.visibility AS series_visible FROM oc_seminar_episodes AS ose
            LEFT JOIN oc_seminar_series AS oss USING (series_id, seminar_id)
            WHERE 1"
        );

        $db->exec('START TRANSACTION');

        while($data = $results->fetch()) {

            $visibility = 'hidden';

            // if the series
            if ($data['series_visible'] == 'visible') {
                switch ($data['visibility']) {
                    case 'invisible':
                        $visibility = 'hidden';
                        break;
                    case 'visible':
                        $visibility = 'visible';
                        break;
                }
            }

            $video = Videos::findOneByEpisode($data['episode_id']);

            if (!empty($video)) {
                $stmt_sem->execute([
                    ':video_id'   => $video->id,
                    ':seminar_id' => $data['seminar_id'],
                    ':vis'        => $visibility
                ]);
            }
        }

        $db->exec('COMMIT');

        $db->exec("DROP TABLE oc_seminar_episodes");

        SimpleOrMap::expireTableScheme();
    }

    function down() {

    }
}
