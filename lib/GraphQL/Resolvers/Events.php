<?php

namespace Opencast\GraphQL\Resolvers;

use \DBManager;
use Opencast\Models\SeminarSeries;
//use Opencast\Models\OCCourseModel;
use Opencast\Models\REST\SearchClient;
use Opencast\Models\REST\ApiEventsClient;

class Events
{
    function getEvents($root, $args, $context)
    {
        $course_id = $args['course_id'];
        $user_id   = $context['user_id'];

        if (!$GLOBALS['perm']->have_studip_perm('user', $course_id, $user_id)) {
            die('access');
            throw new AccessDeniedException();
        }

        $connectedSeries = SeminarSeries::getSeries($course_id);


        if (!$connectedSeries) {
            return null;
        }

        //$seriesList = [];
        $events = [];


        foreach ($connectedSeries as $series) {
            // check series visibility
            if ($series->visibility == 'visible'
                || $GLOBALS['perm']->have_studip_perm('tutor', $course_id, $user_id)
            ) {
                // get correct endpoint for current series
                $eventsClient = ApiEventsClient::getInstance($series['config_id']);
                //$seriesList[$series['series_id']]['events'] = $eventsClient->getBySeries($series['series_id']);
                $events = array_merge($events, $eventsClient->getBySeries($series['series_id']));
            }
        }

        $result = [];

        // conform events to schema
        foreach ($events as $event) {
            $results[] = [
                'id'      => $event['identifier'],
                'title'   => $event['title'],
                'author'  => reset($event['presenter']),
                'mk_date' => strtotime($event['created'])
            ];
        }

        return $results;
    }
}
