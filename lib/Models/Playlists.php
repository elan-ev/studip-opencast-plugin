<?php

namespace Opencast\Models;

class Playlists extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist';

        $config['has_many']['perms'] = [
            'class_name' => 'Opencast\\Models\\PlaylistsUserPerms',
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

        return $data;
    }
}
