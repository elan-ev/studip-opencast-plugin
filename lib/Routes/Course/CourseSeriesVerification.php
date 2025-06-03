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

        // Check if user has proper right to perform this action.
        // [User must be enrolled in the seminar]
        // [User must have the right to upload in the seminar!]
        if (!$perm->have_studip_perm('user', $course_id) || !Perm::uploadAllowed($course_id)) {
            // throw new \AccessDeniedException();
            // If the user does not have the right to upload, we don't throw an error, but we return a message that this process cannot be performed!
            return $this->createResponse([
                'message' => [
                    'type' => 'warning',
                    'text' => _('Die Überprüfung der Serie ist nicht möglich!'),
                ]
            ], $response);
        }

        // Default message, not necessarily needed, but it makes it easier to understand what is going on and makes the debugging and higher level information easier.
        $message = [
            'type' => 'success',
            'text' => _('Die Serie ist gültig.'),
        ];
        try {
            // Set a flag to force re-creationg of the series in Opencast.
            $recreate_series_needed = false;
            $series = SeminarSeries::findBySeries_id($series_id);

            if (empty($series)) {
                // If the series is not found, we need to create one.
                $recreate_series_needed = true;
            } else {
                // If the series is found, we need to check if it is still valid.
                $series_client = new SeriesClient($config_id);
                $series_data = $series_client->getSeries($series_id, true);
                if ($series_data == 404) {
                    $recreate_series_needed = true;
                }
            }

            if ($recreate_series_needed) {

                $series_client = new SeriesClient($config_id);
                $series_id = $series_client->createSeriesForSeminar($course_id);

                if ($series_id) {
                    $series = SeminarSeries::create([
                        'config_id'  => $config_id,
                        'seminar_id' => $course_id,
                        'series_id'  => $series_id,
                    ]);
                    $series->store();
                }
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

        return $this->createResponse([
            'message' => $message,
        ], $response);
    }
}
