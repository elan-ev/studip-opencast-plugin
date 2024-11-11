<?php

require_once __DIR__ . '/../bootstrap_migrations.php';

use Opencast\Models\Playlists;
use Opencast\Models\PlaylistSeminars;
use Opencast\Models\PlaylistsUserPerms;
use Opencast\Models\PlaylistTags;
use Opencast\Models\PlaylistVideos;

class DuplicateLinkedPlaylists extends Migration
{
    public function description()
    {
        return 'Duplicate playlists with more than one linked course to ensure that playlists belong to only one course';
    }

    public function up()
    {
        $playlists = Playlists::findBySQL('1');

        foreach ($playlists as $playlist) {
            $courses = $playlist->courses;

            if (count($courses) > 1) {
                // Duplicate playlists for n-1 courses
                for ($i = 0; $i < count($courses) - 1; $i++) {
                    $course = $courses[$i];

                    // Copy playlist
                    $new_playlist = $this->copyPlaylist($playlist);

                    $playlist_seminar = PlaylistSeminars::findOneBySQL('playlist_id = ? AND `seminar_id` = ?', [
                        $playlist->id,
                        $course->id,
                    ]);

                    // Link playlist copy to course
                    PlaylistSeminars::create([
                        'playlist_id' => $new_playlist->id,
                        'seminar_id'  => $course->id,
                        'is_default'  => $playlist_seminar->is_default,
                        'visibility'  => 'visible',
                        'allow_download'  => $playlist_seminar->allow_download,
                    ]);

                    // Remove link to original playlist
                    $playlist_seminar->delete();
                }
            }
        }
    }

    public function copyPlaylist($playlist) {
        // Copy playlist
        $new_playlist = Playlists::create([
            'title'          => $playlist->title,
            'visibility'     => $playlist->visibility,
            'chdate'         => $playlist->chdate,
            'mkdate'         => $playlist->mkdate,
            'sort_order'     => $playlist->sort_order,
            'allow_download' => $playlist->allow_download,
        ]);

        // Copy user perms
        foreach ($playlist->perms as $perm) {
            PlaylistsUserPerms::create([
                'playlist_id' => $new_playlist->id,
                'user_id'     => $perm->user_id,
                'perm'        => $perm->perm
            ]);
        }

        // Link videos to new playlist
        foreach ($playlist->videos as $video) {
            PlaylistVideos::create([
                'playlist_id' => $new_playlist->id,
                'video_id'    => $video->video_id,
                'order'       => $video->order,
                'chdate'      => $video->chdate,
                'mkdate'      => $video->mkdate,
            ]);
        }

        // Copy tags
        foreach ($playlist->tags as $tag) {
            PlaylistTags::create([
                'playlist_id' => $new_playlist->id,
                'tag_id' => $tag->id,
            ]);
        }

        $new_playlist->store();

        return $new_playlist;
    }

    public function down() {}
}