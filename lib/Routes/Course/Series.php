<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\SeminarSeries;
use Opencast\Models\Endpoints;
use Opencast\Models\REST\ApiSeriesClient;
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
                $sclient = ApiSeriesClient::getInstance($entry['config_id']);
                $series[$key]['details'] = $sclient->getSeries($entry['series_id']);

                // get upload url for this series
                $ingest_url = reset(Endpoints::findBySql("config_id = ? AND service_type = 'ingest'", [$entry['config_id']]));
                $series[$key]['ingest_url'] = $ingest_url['service_url'];
            }


            return $this->createResponse(['series' => $series], $response);
        } else {
            throw new AccessDeniedException();
        }
    }
}
