<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Filter;
use Opencast\Models\Videos;
use Opencast\Caching\VideosCaching;

class CourseVideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $course_id = $args['course_id'];

        $params = $request->getQueryParams();

        if (!$params['cid']) {
            $params['cid'] = $course_id;
        }

        // check if user has access to this seminar
        if (!$perm->have_studip_perm('user', $course_id)) {
            throw new \AccessDeniedException();
        }

        $response_result = [
            'videos' => [],
            'count'  => 0,
        ];

        $filter = new Filter($params);
        $video_caching = new VideosCaching();
        $course_videos_cache = $video_caching->courseVideos($course_id);
        $unique_query_id = $filter->decodeVars();
        $response_result = $course_videos_cache->read($unique_query_id);
        if (empty($response_result)) {
            // show videos for this course and filter them with optional additional filters
            $videos = Videos::getCourseVideos($course_id, $filter);

            $ret = [];
            foreach ($videos['videos'] as $video) {
                $video_array = $video->toSanitizedArray($params['cid']);
                if (!empty($video_array['perm']) && ($video_array['perm'] == 'owner' || $video_array['perm'] == 'write'))
                {
                    $video_array['perms'] = $video->perms->toSanitizedArray();
                }
                $ret[] = $video_array;
            }

            $response_result = [
                'videos' => $ret,
                'count'  => $videos['count'],
            ];

            $course_videos_cache->write($unique_query_id, $response_result);
        }

        return $this->createResponse($response_result, $response);
    }
}
