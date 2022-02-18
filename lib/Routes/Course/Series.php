<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\SeminarSeries;
use Opencast\Models\REST\SeriesClient;
use Opencast\Providers\Perm;

class Series extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $course_id = $args['course_id'];

        if (Perm::editAllowed($course_id)) {
            $series_collection = new \SimpleCollection(SeminarSeries::findBySeminar_id($course_id));
            $series = $series_collection->toArray();

            foreach($series as $key => $entry) {
                $sclient = SeriesClient::getInstance($entry['config_id']);
                $series[$key]['details'] = $sclient->getSeries($entry['series_id']);
            }


            return $this->createResponse(['series' => $series], $response);
        } else {
            throw new AccessDeniedException();
        }
    }
}
