<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\SeminarSeries;
use Opencast\Models\REST\ApiSeriesClient;
use Opencast\Models\LTI\OpencastLTI;
use Opencast\Models\LTI\ACL;
use Opencast\Providers\Perm;

class SeriesDelete extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $course_id = $args['course_id'];
        $series_id = $args['series_id'];

        if (Perm::editAllowed($course_id)) {

            $check = SeminarSeries::findBySql('seminar_id = ? AND series_id = ?', [
                $course_id, $series_id
            ])[0];


            if (!empty($check)) {
                $check->delete();
            }

            return $this->createResponse([], $response);
        } else {
            throw new AccessDeniedException();
        }
    }
}
