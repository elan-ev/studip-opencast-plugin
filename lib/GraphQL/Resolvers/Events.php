<?php

namespace Opencast\GraphQL\Resolvers;

use \DBManager;
use Opencast\Models\SeminarSeries;
//use Opencast\Models\OCCourseModel;
use Opencast\Models\REST\SearchClient;
use Opencast\Models\REST\ApiEventsClient;
use Opencast\Models\REST\AdminNgEventClient;
use Opencast\Models\Pager;

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

        $results = [
            'events'    => [],
            'page_info' => [
                'total_items'  => 0,
                'current_page' => 0,
                'last_page'    => 0
            ]
        ];

        if (empty($connectedSeries)) {
            return $results;
        }

        //$seriesList = [];
        $events  = [];

        Pager::setPage($args['page']);
        Pager::setLimit($args['limit']);
        Pager::setSordOrder($args['sort']);
        Pager::setSearch($args['search']);

        foreach ($connectedSeries as $series) {
            // check series visibility
            if ($series->visibility == 'visible'
                || $GLOBALS['perm']->have_studip_perm('tutor', $course_id, $user_id)
            ) {
                // get correct endpoint for current series
                $eventsClient = ApiEventsClient::getInstance($series['config_id']);
                //$seriesList[$series['series_id']]['events'] = $eventsClient->getBySeries($series['series_id']);
                $events = array_merge($events, $eventsClient->getBySeries($series['series_id'], $course_id));
            }
        }

        // paginate events
        $num_events = Pager::getLength();
        $offset = Pager::getOffset();
        $limit = Pager::getLimit();

        if (!empty($events)) {
            $results['page_info'] = [
                'total_items'  => Pager::getLength(),
                'current_page' => Pager::getPage(),
                'last_page'    => floor((Pager::getLength() - 1) / Pager::getLimit()),
            ];
        }

        // conform events to schema
        foreach ($events as $event) {
            $downloads = [];
            foreach($event['presentation_download'] as $size => $download) {
                $downloads[] = [
                    'type' => 'presentation_download',
                    'url' => $download['url'],
                    'info' => $download['info'],
                    'size' => $size
                ];
            }
            foreach($event['presenter_download'] as $size => $download) {
                $downloads[] = [
                    'type' => 'presenter_download',
                    'url' => $download['url'],
                    'info' => $download['info'],
                    'size' => $size
                ];
            }

            $results['events'][] = [
                'id'              => $event['id'],
                'title'           => $event['title'],
                'author'          => $event['author'],
                'contributor'     => $event['contributor'],
                'track_link'      => $event['track_link'],
                'length'          => $event['duration'],
                'downloads'       => $downloads,
                'annotation_tool' => $event['annotation_tool'],
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
