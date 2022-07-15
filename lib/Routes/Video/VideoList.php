<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Filter;
use Opencast\Models\Videos;
use Opencast\Models\Tags;

class VideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        // select all videos the current user has perms on
        $params = [
            ':user_id'=> $user->id
        ];

        $filters = new Filter($request);

        $sql  = ' INNER JOIN oc_video_user_perms AS p ON (p.user_id = :user_id AND p.video_id = id) ';

        $where = ' WHERE 1 ';
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

        return $this->createResponse([
            'videos' => $ret,
            'count'  => $count
        ], $response);
    }
}
