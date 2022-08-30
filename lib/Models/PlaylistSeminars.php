<?php

namespace Opencast\Models;

class PlaylistSeminars extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_seminar';

        $config['belongs_to']['playlist'] = [
            'class_name' => 'Opencast\\Models\\Playlists',
            'foreign_key' => 'playlist_id',
        ];

        $config['has_many']['seminar_videos'] = [
            'class_name' => 'Opencast\\Models\\PlaylistSeminarVideos',
            'assoc_foreign_key' => 'playlist_seminar_id',
        ];

        parent::configure($config);
    }

    /**
     * Get sanitized array to send to the frontend
     */
    public function toSanitizedArray()
    {
        $playlist_data = $this->playlist->toArray();
        $playlist_data['videos_count'] = count($this->seminar_videos);
        $playlist_data['visibility'] = $this->visibility;

        return $playlist_data;
    }
}
