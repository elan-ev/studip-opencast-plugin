<?php

namespace Opencast\Models;

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

    public function getVideos($user_id, $filters, $playlist_token=null) {
        $params = [
            ':user_id'=> $user_id
        ];

        $sql  = ' INNER JOIN oc_video_user_perms AS p ON (p.user_id = :user_id AND p.video_id = id) ';
        $where = ' WHERE 1 ';
        if ($playlist_token != null) {
            $playlist = Playlists::findOneBySQL('token = ?', [$playlist_token]);
            if ($playlist != null) {
                $sql  .= ' INNER JOIN oc_playlist_video AS pl ON (pl.video_id = id) ';
                $where .= ' AND pl.playlist_id = ' . $playlist->id;
            }
        }

        $tag_ids = [];

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
                        }
                    }
                    break;
            }
        }

        if (!empty($tag_ids)) {
            $sql .= ' INNER JOIN oc_video_tags AS t ON (t.tag_id IN('. implode(',', $tag_ids) .'))';
        }

        $sql .= $where;

        $sql .= ' GROUP BY oc_video.id';

        $stmt = \DBManager::get()->prepare($s = "SELECT COUNT(*) FROM (SELECT oc_video.* FROM oc_video $sql) t");
        $stmt->execute($params);
        $count = $stmt->fetchColumn();

        $sql   .= ' LIMIT '. $filters->getOffset() .', '. $filters->getLimit();
        $videos = Videos::findBySQL($sql, $params);

        $ret = [];
        foreach ($videos as $video) {
            $ret[] = $video->getCleanedArray();
        }

        return [
            'videos' => $ret,
            'count'  => $count
        ];
    }

    public function getCleanedArray()
    {
        $data = $this->toArray();

        $data['chdate'] = ($data['chdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['chdate']);

        $data['mkdate'] = ($data['mkdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['mkdate']);

        return $data;
    }
}
