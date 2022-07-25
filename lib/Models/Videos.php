<?php

namespace Opencast\Models;

use Opencast\Models\Filter;
use Opencast\Models\Tags;
use Opencast\Models\Playlists;

class Videos extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video';

        $config['has_many']['perms'] = [
            'class_name' => 'Opencast\\Models\\VideosUserPerms',
            'assoc_foreign_key' => 'video_id',
        ];

        parent::configure($config);
    }

    public function findByFilter($filters)
    {

        global $user;

        $params = [
            ':user_id'=> $user->id
        ];

        $sql  = ' INNER JOIN oc_video_user_perms AS p ON (p.user_id = :user_id AND p.video_id = id) ';

        $where = ' WHERE 1 ';
        $tag_ids      = [];
        $playlist_ids = [];

        foreach ($filters->getFilters() as $filter) {
            switch ($filter['type']) {
                case 'text':
                    $pname = ':text' . sizeof($params);
                    $where .= " AND (title LIKE $pname OR description LIKE $pname)";
                    $params[$pname] = '%' . $filter['value'] .'%';
                    break;

                case 'tag':
                    // get id of this tag (if any)
                    if (!empty($filter['value'])) {
                        $tags = Tags::findBySQL($sq = 'tag LIKE ?',  $pr = ['%'. $filter['value'] .'%']);

                        if (!empty($tags)) {
                            foreach ($tags as $tag) {
                                $tag_ids[] = $tag->id;
                            }
                        } else {
                            $tag_ids[] = '-1';
                        }
                    }
                    break;

                case 'playlist':
                    $playlists = Playlists::findByToken($filter['value']);

                    if (!empty($playlists)) {
                        foreach ($playlists as $playlist) {
                            $playlist_ids[] = $playlist->id;
                        }
                    } else {
                        $playlist_ids[] = '-1';
                    }

                    break;
            }
        }

        if (!empty($tag_ids)) {
            $sql .= ' INNER JOIN oc_video_tags AS t ON (t.tag_id IN('. implode(',', $tag_ids) .'))';
        }

        if (!empty($playlist_ids)) {
            $sql .= ' INNER JOIN oc_playlist_video AS opv ON (opv.playlist_id IN('. implode(',', $playlist_ids) .'))';
        }

        $sql .= $where;

        $sql .= ' GROUP BY oc_video.id';

        $stmt = \DBManager::get()->prepare($s = "SELECT COUNT(*) FROM (SELECT oc_video.* FROM oc_video $sql) t");
        $stmt->execute($params);
        $count = $stmt->fetchColumn();

        $sql   .= ' LIMIT '. $filters->getOffset() .', '. $filters->getLimit();

        return [
            'videos' => self::findBySQL($sql, $params),
            'count'  => $count
        ];
    }

    public function toSanitizedArray()
    {
        $data = $this->toArray();

        $data['chdate'] = ($data['chdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['chdate']);

        $data['mkdate'] = ($data['mkdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['mkdate']);

        return $data;
    }
}
