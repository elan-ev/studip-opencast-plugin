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

    public function getepisodes_action($series_id)
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

        $this->render_json(array_values($result));
    }

    /**
     * @param $workflow_id
     * @throws Exception
     * @throws Trails_DoubleRenderError
     */
    public function getWorkflowStatus_action($workflow_id)
    {
        if ($workflow = OCSeminarWorkflows::find($workflow_id)) {
            $this->workflow_client = WorkflowClient::getInstance($workflow->config_id);
            $resp                  = $this->workflow_client->getWorkflowInstance($workflow_id);
            $this->render_text(json_encode($resp));
            return;
        }

        $this->render_nothing();
    }

    public function getWorkflowStatusforCourse_action($course_id)
    {
        $workflow_ids          = OCModel::getWorkflowIDsforCourse($course_id);
        $this->workflow_client = WorkflowClient::getInstance(OCConfig::getConfigIdForCourse($course_id));
        if (!empty($workflow_ids)) {
            $states = OCModel::getWorkflowStates($course_id, $workflow_ids);
            $this->render_text(json_encode($states));
        } else {
            $this->render_nothing();
        }
    }
}
