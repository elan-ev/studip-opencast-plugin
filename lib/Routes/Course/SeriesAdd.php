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

class SeriesAdd extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $course_id = $args['course_id'];
        $json = $this->getRequestData($request);

        if (Perm::editAllowed($course_id)) {

            $check = SeminarSeries::findBySql('seminar_id = ? AND series_id = ?', [
                $course_id, $json['series_id']
            ]);

            if (empty($check)) {
                $series = new SeminarSeries();
                $series->setData([
                    'seminar_id' => $course_id,
                    'series_id'  => $json['series_id'],
                    'config_id'  => $json['config_id'],
                    'visibility' => ACL::getDefaultVisibility($course_id)
                ]);
                $series->store();

                ACL::setForSeries($course_id, $json['config_id'], $json['series_id']);

                $results = $series->toArray();

                $sclient = ApiSeriesClient::getInstance($json['config_id']);
                $results['details'] = $sclient->getSeries($series['series_id']);

                return $this->createResponse(['series' => $results], $response);
            } else {
                return $this->createResponse([], $response);
            }
        } else {
            throw new AccessDeniedException();
        }
    }
}
