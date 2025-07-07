<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\SeminarSeries;
use Opencast\Models\REST\SeriesClient;
use Opencast\Providers\Perm;


/**
 * Make sure that a series exists for the course. If not, create a new one!
 */
class CourseSeriesVerification extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $course_id = $args['course_id'];
        $json = $this->getRequestData($request);

        $series_id = $json['series_id'] ?? null;
        if (empty($series_id) || empty($course_id)) {
            throw new Error('Es fehlen Parameter!', 422);
        }

        $config_id = $json['config_id'] ?? \Config::get()->OPENCAST_DEFAULT_SERVER;

        $message = [
            'type' => 'success',
            'text' => _('Die Serie ist gültig.'),
        ];
        try {
            $series_has_been_recreated = SeminarSeries::ensureSeriesExists($config_id, $course_id, $series_id);
            if ($series_has_been_recreated) {
                // The goal is to create a new one when it does not exist, so we return 201 Created.
                return $response->withStatus(201);
            }
        } catch (\Throwable $th) {
            // If something goes wrong, we catch the error and return the message but in 200 code so that it does not break the flow of the application.
            $message = [
                'type' => 'error',
                'text' => _('Die Überprüfung der Serie ist fehlergeschlagen') . ': ' . $th->getMessage(),
            ];
        }

        // In any case, we are obliged to return something in favor of sticking to API Response.
        return $this->createResponse([
            'message' => $message,
        ], $response);
    }
}
