<?php

use Opencast\Models\OCConfig;

class AjaxController extends OpencastController
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        $this->plugin = $dispatcher->current_plugin;

        // Localization
        $this->_ = function ($string) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_'],
                func_get_args()
            );
        };

        $this->_n = function ($string0, $tring1, $n) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_n'],
                func_get_args()
            );
        };
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws RuntimeException when method is not found
     */
    public function __call($method, $arguments)
    {
        $variables = get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        throw new RuntimeException("Method {$method} does not exist");
    }

    function before()
    {
        // notify on trails action
        $klass = substr(get_called_class(), 0, -10);
        $name = sprintf('oc_embed.performed.%s_%s', $klass, $action);
        NotificationCenter::postNotification($name, $this);
    }

    function index_action()
    {
        $this->render_text($this->_("Ups.."));
    }

    function getseries_action()
    {
        global $perm;

        $series = OCSeriesModel::getSeriesForUser($GLOBALS['user']->id);

        array_walk($series, function(&$item, $key) {
            $sem = Course::find($item['seminar_id']);
            $item['name'] = $sem->getFullname('number-name-semester');
            $item['endtime'] = $sem->getEnd_Time();
            $item = array_merge($item, OCSeriesModel::getSeriesFromOpencast($item));
        });

        uasort($series, function($a, $b) {
            return $a['endtime'] == $b['endtime'] ? 0
                : $a['endtime'] < $b['endtime'] ? -1 : 1;
        });

        $this->render_json($series);
    }

    function getepisodes_action($series_id)
    {

        $search_client = SearchClient::getInstance(OCConfig::getConfigIdForSeries($series_id));
        //$episodes      = $search_client->getEpisodes($series_id);
        $result        = [];


        $course = Course::find(Context::getId());
        $role = '';

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
            if (key_exists('mediapackage', $episode)){
                $result[] = $episode;
            }
        }

        $this->render_json($result);
    }

    /**
     * @param $workflow_id
     * @throws Exception
     * @throws Trails_DoubleRenderError
     */
    function getWorkflowStatus_action($workflow_id)
    {
        if ($workflow = OCSeminarWorkflows::find($workflow_id)) {
            $this->workflow_client = WorkflowClient::getInstance($workflow->config_id);
            $resp = $this->workflow_client->getWorkflowInstance($workflow_id);
            $this->render_text(json_encode($resp));
            return;
        }

        $this->render_nothing();
    }

    function getWorkflowStatusforCourse_action($course_id)
    {
        $workflow_ids = OCModel::getWorkflowIDsforCourse($course_id);
        $this->workflow_client = WorkflowClient::getInstance(OCConfig::getConfigIdForCourse($course_id));
        if (!empty($workflow_ids)) {
            $states = OCModel::getWorkflowStates($course_id, $workflow_ids);

            $this->render_text(json_encode($states));
        } else {
            $this->render_nothing();
        }
    }

}
