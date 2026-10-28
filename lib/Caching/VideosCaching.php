<?php

namespace Opencast\Caching;

use Opencast\Models\Videos;

class VideosCaching
{
    private $cache_factory;
    private $cache_name;
    const OC_CACHE_KEY_DOMAIN_USERS = 'OpencastV3/videos/users/';
    const OC_CACHE_KEY_DOMAIN_COURSES = 'OpencastV3/videos/courses/';
    const OC_CACHE_KEY_DOMAIN_PLAYLIST = 'OpencastV3/videos/playlist/';

    public function __construct() {
        $this->cache_factory = \StudipCacheFactory::getCache();
    }

    public function userVideos(string $user_id)
    {
        $this->cache_name = self::OC_CACHE_KEY_DOMAIN_USERS . $user_id;
        return $this;
    }

    public function courseVideos(string $course_id)
    {
        $this->cache_name = self::OC_CACHE_KEY_DOMAIN_COURSES . $course_id;
        return $this;
    }

    public function playlistVideos(int $playlist_id)
    {
        $this->cache_name = self::OC_CACHE_KEY_DOMAIN_PLAYLIST . $playlist_id;
        return $this;
    }

    public function readAll()
    {
        if (empty($this->cache_name)) {
            throw new \Error('Unable to read the cache due to missing cache name!');
        }

        $content = $this->cache_factory->read($this->cache_name);
        return $content ? unserialize($content) : [];
    }

    public function read(string $unique_query_id)
    {
        if (empty($unique_query_id)) {
            throw new \Error('Unable to read the cache due to missing cache name!');
        }

        $all = $this->readAll();

        if (!isset($all[$unique_query_id])) {
            return false;
        }

        return $all[$unique_query_id];
    }

    public function write($unique_query_id, $content)
    {
        if (empty($unique_query_id)) {
            throw new \Error('Unable to write the cache data due to missing cache name!');
        }

        $all = $this->readAll();

        $all[$unique_query_id] = $content;

        $serialized_records = serialize($all);

        return $this->cache_factory->write($this->cache_name, $serialized_records);
    }

    public function delete($unique_query_id)
    {
        if (empty($unique_query_id)) {
            throw new \Error('Unable to expire the cache data due to missing cache name!');
        }

        $all = $this->readAll();

        if (empty($all)) {
            return true;
        }

        if (isset($all[$unique_query_id])) {
            unset($all[$unique_query_id]);
        }

        if (empty($all)) {
            $this->expire();
            return true;
        }

        return $this->cache_factory->write($this->cache_name, serialize($all));
    }

    public function expire()
    {
        $this->cache_factory->expire($this->cache_name);
    }

    public static function expireAllVideoCaches(Videos $video)
    {
        $cache = \StudipCacheFactory::getCache();
        if (!empty($video->perms)) {
            foreach ($video->perms->pluck('user_id') as $user_id) {
                $cache->expire(self::OC_CACHE_KEY_DOMAIN_USERS . $user_id);
            }
        }

        if (!empty($video->playlists)) {
            foreach ($video->playlists as $playlist) {
                $cache->expire(self::OC_CACHE_KEY_DOMAIN_PLAYLIST . $playlist->id);

                if (!empty($playlist->courses)) {
                    foreach ($playlist->courses as $course) {
                        $cache->expire(self::OC_CACHE_KEY_DOMAIN_COURSES . $course->id);
                    }
                }
            }
        }

        // We need to also look for root accounts!
        $stmt = \DBManager::get()->prepare($q = "SELECT `user_id` FROM `auth_user_md5` WHERE `perms` = :perm");
        $stmt->execute([
            ':perm'   => 'root',
        ]);
        $root_user_ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($root_user_ids as $root_user_id) {
            $cache->expire(self::OC_CACHE_KEY_DOMAIN_USERS . $root_user_id);
        }
    }
}
