<?php

require_once __DIR__ . '/../bootstrap_migrations.php';

use Opencast\Models\Playlists;
use Opencast\Models\PlaylistVideos;
use Opencast\Models\PlaylistSeminars;
use Opencast\Models\PlaylistSeminarVideos;

class ConvertVirtualPlaylists extends Migration
{
    public function description()
    {
        return 'Convert virtual course playlists to real playlists and populate the visibility of the videos in oc_playlist_seminar_video';
    }

    public function up()
    {
        $db = DBManager::get();

        // fix oc_seminar_series table
        $db->exec('SET foreign_key_checks = 0');
        $db->exec('ALTER TABLE `oc_seminar_series`
            DROP FOREIGN KEY `oc_seminar_series_ibfk_1`,
            ADD FOREIGN KEY oc_seminar_series_ibfk_2 (`config_id`) REFERENCES `oc_config` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        $db->exec('SET foreign_key_checks = 1');

        // add switch to playlist-course relation to denote if this is the default playlist for this course
        $db->exec('ALTER TABLE oc_playlist_seminar
            ADD `is_default` tinyint DEFAULT 0 AFTER seminar_id');

        SimpleOrMap::expireTableScheme();

        $result = $db->query("SELECT * FROM oc_video_seminar");

        // collect videos for playlists
        $playlists = [];
        while ($data = $result->fetch(PDO::FETCH_ASSOC)) {
            $playlists[$data['seminar_id']][] = [$data['video_id'], $data['visibility']];
        }

        // create playlists for courses, add videos to them and connect these new playlists to the course
        foreach ($playlists as $seminar_id => $videos_records) {
            $course = Course::find($seminar_id);

            if (!empty($course)) {
                // create playlist
                $playlist = new Playlists();
                $playlist->title      = $course->getFullname('number-name-semester');
                $playlist->token      = bin2hex(random_bytes(8));
                $playlist->visibility = 'internal';
                $playlist->store();

                // add videos to playlist
                foreach ($videos_records as $video_record) {
                    [$video_id, $video_visibility] = $video_record;
                    $video = new PlaylistVideos();
                    $video->video_id    = $video_id;
                    $video->playlist_id = $playlist->id;
                    $video->store();
                }

                // connect playlist to course
                $psem = new PlaylistSeminars();
                $psem->playlist_id = $playlist->id;
                $psem->seminar_id  = $seminar_id;
                $psem->is_default  = 1;
                $psem->visibility  = 'visible';
                $psem->store();

                // Populate videos visibilities in oc_playlist_seminar_video table.
                foreach ($videos_records as $video_record) {
                    [$video_id, $video_visibility] = $video_record;
                    $psemv = new PlaylistSeminarVideos();
                    $psemv->playlist_seminar_id = $psem->id;
                    $psemv->video_id = $video_id;
                    $psemv->visibility = $video_visibility;
                    $psemv->store();
                }
            }
        }

        // remove obsolete table
        $db->exec('DROP TABLE `oc_video_seminar`');

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        SimpleOrMap::expireTableScheme();
    }
}
