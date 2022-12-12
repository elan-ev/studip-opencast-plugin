<?php

namespace Opencast\Models;

class VideosShares extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_shares';

        $config['belongs_to']['video'] = [
            'class_name' => 'Opencast\\Models\\Videos',
            'foreign_key' => 'video_id',
        ];

        parent::configure($config);
    }

    public static function generateToken() {
        do {
            $token = bin2hex(random_bytes(8));
            $exists = self::findByToken($token);
        } while (!empty($exists));
        return $token;
    }

    public static function generateUuid() {
        do {
            $uuid = bin2hex(random_bytes(16));
            $uuid = substr($uuid, 0, 32);
            $exists = self::findByUuid($uuid);
        } while (!empty($exists));
        return $uuid;
    }

    public static function findByToken($token)
    {
        return self::findOneBySQL('token = ?', [$token]);
    }

    public static function findByUuid($uuid)
    {
        return self::findOneBySQL('uuid = ?', [$uuid]);
    }
}
