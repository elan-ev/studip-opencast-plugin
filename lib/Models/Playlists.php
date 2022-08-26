<?php

namespace Opencast\Models;

class Playlists extends UPMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist';

        $config['has_many']['perms'] = [
            'class_name' => 'Opencast\\Models\\PlaylistsUserPerms',
            'assoc_foreign_key' => 'playlist_id',
        ];

        $config['has_and_belongs_to_many']['tags'] = [
            'class_name'     => 'Opencast\\Models\\Tags',
            'thru_table'     => 'oc_playlist_tags',
            'thru_key'       => 'playlist_id',
            'thru_assoc_key' => 'tag_id'
        ];

        $config['has_many']['videos'] = [
            'class_name' => 'Opencast\\Models\\PlaylistVideos',
            'assoc_foreign_key' => 'playlist_id',
        ];

        parent::configure($config);
    }

    public static function findByUser_id($user_id)
    {
        return self::findBySQL('LEFT JOIN oc_playlist_user_perms AS ocp
            ON (ocp.playlist_id = oc_playlist.id)
            WHERE ocp.user_id = ?', [$user_id]);
    }

    public static function findByCourse_id($course_id)
    {
        return self::findBySQL('LEFT JOIN oc_playlist_seminar AS ocps
            ON (ocps.playlist_id = oc_playlist.id)
            WHERE ocps.seminar_id = ?', [$course_id]);
    }

    /**
     * Get sanitized array to send to the frontend
     */
    public function toSanitizedArray()
    {
        $data = $this->toArray();
        unset($data['id']);
        $data['videos_count'] = count($this->videos);

        $data['tags'] = $this->tags->toArray();

        return $data;
    }
}
