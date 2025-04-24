<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\SeminarSeries;
use Opencast\Models\SeminarWorkflowConfiguration;
use Opencast\Models\REST\SeriesClient;
use Opencast\Providers\Perm;
use Opencast\Models\Helpers;


/**
 * Find the playlists for the passed course
 */
class CourseConfig extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $course_id = $args['course_id'];

        if (!$perm->have_studip_perm('user', $course_id)) {
            throw new \AccessDeniedException();
        }

        $series = SeminarSeries::findOneBySeminar_id($course_id);

        if (empty($series)) {
            // only tutor or above should be able to trigger this series creation!
            $required_course_perm = \Config::get()->OPENCAST_TUTOR_EPISODE_PERM ? 'tutor' : 'dozent';
            if ($perm->have_studip_perm($required_course_perm, $course_id)) {
                // No series for this course yet! Create one!
                $config_id = \Config::get()->OPENCAST_DEFAULT_SERVER;
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
            }
        }

        // Default Course Episodes Visibility.
        // The course specific config option OPENCAST_COURSE_DEFAULT_EPISODES_VISIBILITY has 3 possible values:
            // - default: use the default value from the config (OPENCAST_HIDE_EPISODES) at the time!
            // - visible: show the episodes to students by default
            // - hidden: hide the episodes from students by default
        // Getting with the default value from the config (OPENCAST_HIDE_EPISODES).
        $course_hide_episodes = \Config::get()->OPENCAST_HIDE_EPISODES;
        $course_default_episodes_visibility = \CourseConfig::get($course_id)->OPENCAST_COURSE_DEFAULT_EPISODES_VISIBILITY
                                                ?? 'default';
        if ($course_default_episodes_visibility !== 'default') {
            $course_hide_episodes = $course_default_episodes_visibility === 'hidden' ? true : false;
        }

        $results = [
            'series'    => [
                'series_id'  => $series->series_id,
            ],
            'workflow'                           => SeminarWorkflowConfiguration::getWorkflowForCourse($course_id),
            'edit_allowed'                       => Perm::editAllowed($course_id),
            'upload_allowed'                     => Perm::uploadAllowed($course_id),
            'upload_enabled'                     => \CourseConfig::get($course_id)->OPENCAST_ALLOW_STUDENT_UPLOAD ? 1 : 0,
            'has_default_playlist'               => Helpers::checkCourseDefaultPlaylist($course_id),
            'scheduling_allowed'                 => Perm::schedulingAllowed($course_id),
            'course_hide_episodes'               => $course_hide_episodes, // Use this in a course instead of OPENCAST_HIDE_EPISODES!
            'course_default_episodes_visibility' => $course_default_episodes_visibility,
        ];

        return $this->createResponse($results, $response);
    }
}
