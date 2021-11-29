<?php

namespace Opencast\GraphQL\Resolvers;

use \DBManager;
use Opencast\Models\SeminarSeries;
//use Opencast\Models\OCCourseModel;
use Opencast\Models\REST\SearchClient;
use Opencast\Models\REST\ApiEventsClient;
use Opencast\Models\REST\AdminNgEventClient;

class Events
{
    /**
     * return events for the passed course in the current users context
     *
     * @param  [type] $root                  [description]
     * @param  [type] $args                  [description]
     * @param  [type] $context               [description]
     *
     * @return [type]          [description]
     */
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
            $track_link = '';
            $length = 0;
            $annotation_tool = '';
            $publications = $eventsClient->getEpisode($event['identifier'], true)[1]->publications;
            foreach ($publications as $publication) {
                if ($publication->channel == 'engage-player') {
                    $track_link = $publication->url;
                    if ($event['duration']) {
                        $length = $event['duration'];
                    }
                }
                if ($publication->channel == 'annotation_tool') {
                    $annotation_tool = $publication->url;
                }
            }

            $results[] = [
                'id'              => $event['identifier'],
                'title'           => $event['title'],
                'author'          => reset($event['presenter']),
                'track_link'      => $track_link,
                'length'          => $length,
                'annotation_tool' => $annotation_tool,
                'description'     => $event['description'],
                'mk_date'         => strtotime($event['created'])
            ];
        }

        return $results;
    }

    /**
     * TODO
     *
     * [addEvent description]
     *
     * @param [type] $root     [description]
     * @param [type] $args     [description]
     * @param [type] $context  [description]
     *
      * @return [type]          [description]
     */
    function addEvent($root, $args, $context)
    {
        $course_id = $args['course_id'];
        $user_id   = $context['user_id'];

        if (!$GLOBALS['perm']->have_studip_perm('autor', $course_id, $user_id)) {
            die('access');
            throw new AccessDeniedException();
        }

        return $args['input'];
    }

    /**
     * TODO
     *
     * [removeEvent description]
     *
     * @param  [type] $root                  [description]
     * @param  [type] $args                  [description]
     * @param  [type] $context               [description]
     *
     * @return [type]          [description]
     */
    function removeEvent($root, $args, $context)
    {
        $course_id = $args['course_id'];
        $user_id   = $context['user_id'];

        if (!$GLOBALS['perm']->have_studip_perm('autor', $course_id, $user_id)) {
            die('access');
            throw new AccessDeniedException();
        }
        return [
            'id'     => $args['id'],
            'title'  => '',
            'author' => ''
        ];
        /*
        $adminng_client = AdminNgEventClient::getInstance();
        $adminng_client->deleteEpisode($args['id']);
        return null;
        */
    }
}
