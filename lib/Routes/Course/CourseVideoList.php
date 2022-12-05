<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Filter;
use Opencast\Models\Videos;

class CourseVideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $params = $request->getQueryParams();

        if (!$args['course_id'] || !$perm->have_studip_perm('user', $args['course_id'])) {
            throw new \AccessDeniedException();
        }

        // show videos for this playlist and filter them with optional additional filters
        $videos = Videos::getCourseVideos($args['course_id'], new Filter($params));

        $ret = [];
        foreach ($videos['videos'] as $video) {
            $ret[] = $video->toSanitizedArray();
        }

        return $this->createResponse([
            'videos' => $ret,
            'count'  => $videos['count'],
            'sql'    => $videos['sql']
        ], $response);
    }
}
