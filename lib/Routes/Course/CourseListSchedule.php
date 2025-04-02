<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;
use Opencast\Providers\Perm;

class CourseListSchedule extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $course_id = $args['course_id'];
        $semester_filter = $args['semester_filter'];

        if (empty($course_id)) {
            throw new Error('Es fehlen Parameter!', 422);
        }

        if (!Perm::schedulingAllowed($course_id, $user->id)) {
            throw new \AccessDeniedException();
        }

        $semester_list = ScheduleHelper::getSemesterList($course_id);
        $allow_schedule_alternate = \Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE;

        $schedules = ScheduleHelper::getScheduleList($course_id, $semester_filter);

        $response_data = [
            'semester_list' => $semester_list,
            'schedule_list' => $schedules['schedule_list'],
            'livestream_available' => $schedules['livestream_available'],
            'allow_schedule_alternate' => $allow_schedule_alternate,
        ];

        return $this->createResponse($response_data, $response);
    }
}
