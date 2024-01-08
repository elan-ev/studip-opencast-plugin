<?php

namespace Opencast\Routes\Tags;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Tags;

class TagListForCourseVideos extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $course_id = $args['course_id'];

        // check if user has access to this seminar
        if (!$perm->have_studip_perm('user', $course_id)) {
            throw new \AccessDeniedException();
        }

        $ret = Tags::getCourseVideosTags($course_id);

        return $this->createResponse($ret, $response->withStatus(200));
    }
}
