<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;

class CourseListSchedule extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $course_id = $args['course_id'];
        $semester_filter = $args['semester_filter'];
        
        $semester_list = ScheduleHelper::getSemesterList($course_id);
        $allow_schedule_alternate = \Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE;
        $allow_livestream = ScheduleHelper::checkCourseConfigLivestream($course_id);

        $schedule_list = ScheduleHelper::getScheduleList($course_id, $semester_filter);
        
        $response_data = [
            'semester_list' => $semester_list,
            'schedule_list' => $schedule_list,
            'allow_schedule_alternate' => $allow_schedule_alternate,
            'allow_livestream' => $allow_livestream
        ];
        return $this->createResponse($response_data, $response);
    }
}