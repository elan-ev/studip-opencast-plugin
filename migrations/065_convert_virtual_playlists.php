<?php

use Opencast\Models\Playlists;
use Opencast\Models\PlaylistVideos;
use Opencast\Models\PlaylistSeminars;

class ConvertVirtualPlaylists extends Migration
{
    public function description()
    {
        return 'Convert virtual course playlists to real playlists';
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

        $result = $db->query("SELECT * FROM oc_video_seminar");

        // collect videos for playlists
        $playlists = [];
        while ($data = $result->fetch(PDO::FETCH_ASSOC)) {
            $playlists[$data['seminar_id']][] = $data['video_id'];
        }

        // create playlists for courses, add videos to them and connect these new playlists to the course
        foreach ($playlists as $seminar_id => $videos) {
            $course = Course::find($seminar_id);

            // create playlist
            $playlist = new Playlists();
            $playlist->title      = $course->getFullname('number-name-semester');
            $playlist->token      = bin2hex(random_bytes(8));
            $playlist->visibility = 'internal';
            $playlist->store();

            // add videos to playlist
            foreach ($videos as $video_id) {
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
        }

        // remove obsolete table
        //$db->exec('DROP TABLE `oc_video_seminar`');

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        SimpleOrMap::expireTableScheme();
    }
}