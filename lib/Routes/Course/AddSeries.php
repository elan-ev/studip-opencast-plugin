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
use Opencast\Models\LTI\OpencastLTI;

class AddSeries extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $course_id = $args['course_id'];
        $json = $this->getRequestData($request);

        if ($perm->have_studip_perm('tutor', $course_id)) {

            $check = SeminarSeries::findBySql('seminar_id = ? AND series_id = ?', [
                $course_id, $json['series_id']
            ]);

            if (!empty($check)) {
                $check[0]->delete();
            }

            if (true || empty($check)) {
                $series = new SeminarSeries();
                $series->setData([
                    'seminar_id' => $course_id,
                    'series_id'  => $json['series_id'],
                    'config_id'  => $json['config_id'],
                    'visibility' => 'visible'
                ]);
                $series->store();

                OpencastLTI::setAcls($course_id);

                $results = $series->toArray();

                $sclient = SeriesClient::getInstance($json['config_id']);
                $results[$key]['details'] = $sclient->getSeries($series['series_id']);

                return $this->createResponse(['series' => $results], $response);
            } else {
                return $this->createResponse([], $response);
            }
        } else {
            throw new AccessDeniedException();
        }
    }
}
