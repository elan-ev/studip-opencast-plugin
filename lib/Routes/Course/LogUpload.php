<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\LTI\ACL;
use Opencast\Providers\Perm;

class LogUpload extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $json = $this->getRequestData($request);

        if (Perm::editAllowed($course_id)) {
            \StudipLog::log('OC_UPLOAD_MEDIA',
                $json['workflow_id'],
                $args['course_id'],
                $json['episode_id']
            );
        }
    }
}
