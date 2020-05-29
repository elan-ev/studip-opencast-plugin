<?php

use Opencast\Models\OCConfig;

class AjaxController extends OpencastController
{
    public function index_action()
    {
        $this->render_text($this->_('Ups..'));
    }

    public function getseries_action()
    {
        $series = OCSeriesModel::getSeriesForUser($GLOBALS['user']->id);

        array_walk($series, function (&$item, $key) {
            $sem             = Course::find($item['seminar_id']);
            $item['name']    = $sem->getFullname('number-name-semester');
            $item['endtime'] = $sem->getEnd_Time();
            $item            = array_merge($item, OCSeriesModel::getSeriesFromOpencast($item));
        });

        uasort($series, function ($a, $b) {
            return $a['endtime'] == $b['endtime'] ? 0
                : $a['endtime'] < $b['endtime'] ? -1 : 1;
        });

        $this->render_json(array_values($series));
    }

    public function getepisodes_action($series_id)
    {
        $search_client = SearchClient::getInstance(OCConfig::getConfigIdForSeries($series_id));
        //$episodes      = $search_client->getEpisodes($series_id);
        $result = [];
        $course = Course::find(Context::getId());
        $role   = '';

        if ($GLOBALS['perm']->have_studip_perm('tutor', $course->id)) {
            $role = 'Instructor';
        } else if ($GLOBALS['perm']->have_studip_perm('autor', $course->id)) {
            $role = 'Learner';
        }

        $episodes = $search_client->getEpisodes($series_id, Context::getId(), [$role]);

        if (!is_array($episodes)) {
            $episodes = [$episodes];
        }

        foreach ($episodes as $episode) {
            if (key_exists('mediapackage', $episode)) {
                $result[] = $episode;
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
