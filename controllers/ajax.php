<?php

use Opencast\Models\OCConfig;
use Opencast\Models\OCSeminarEpisodes;

class AjaxController extends OpencastController
{
    public function index_action()
    {
        $this->render_text($this->_('Ups..'));
    }

    public function getseries_action()
    {
        $series = OCSeriesModel::getSeriesForUser($GLOBALS['user']->id, Context::getId());
        $results = [];

        foreach ($series as $series_id => $seminar_id) {
            $course   = Course::find($seminar_id);

            if (!$course || !$oc_items = OCSeriesModel::getSeriesFromOpencast($series_id, $seminar_id)) {
                continue;
            }

            $item = [
                'seminar_id' => $seminar_id,
                'series_id'  => $series_id
            ];

            $item['name']    = $course->getFullname('number-name-semester');
            $item['endtime'] = $course->getEnd_Time();
            $item            = array_merge($item, $oc_items);

            $results[] = $item;
        }

        uasort($results, function ($a, $b) {
            return $a['endtime'] == $b['endtime'] ? 0
                : ($a['endtime'] < $b['endtime'] ? -1 : 1);
        });

        $this->render_json(array_values($results));
    }

    public function getepisodes_action($series_id, $simple = false)
    {
        $search_client = SearchClient::getInstance(OCConfig::getConfigIdForSeries($series_id));
        $course_id     = OCConfig::getCourseIdForSeries($series_id);
        $result = [];

        if (!OCPerm::editAllowed($course_id)
            || !$GLOBALS['perm']->have_studip_perm('autor', $course_id))
        {
            // do not list episodes if user has no permissions in the connected course
            $this->render_json(array_values([]));
            return;
        }

        $episodes = $search_client->getEpisodes($series_id);

        if (!is_array($episodes)) {
            $episodes = [$episodes];
        }

        foreach ($episodes as $episode) {
            if (key_exists('mediapackage', $episode)) {
                $studip_episode = OCSeminarEpisodes::findOneBySQL(
                    'series_id = ? AND episode_id = ? AND seminar_id = ?',
                    [$series_id, $episode->id, $course_id]
                );

                if ($studip_episode && (
                     (Context::getId() == $course_id && $studip_episode->visible != 'invisible'))
                     || $studip_episode->visible == 'free'
                ) {
                    $result[] = $episode;
                }
            }
        }

        if ($simple) {
            $new_result = [];
            foreach ($episodes as $episode) {
                $new_result[] = [
                    'id'   => $episode->id,
                    'name' => $episode->mediapackage->title,
                    'date' => $episode->mediapackage->start,
                    'url'  => $search_client->getBaseURL() . "/paella/ui/watch.html?id=" . $episode->id
                ];
            }
            $result = $new_result;
        }

        $this->render_json(array_values($result));
    }
}
