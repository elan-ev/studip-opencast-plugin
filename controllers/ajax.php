<?php

require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/WorkflowClient.php';
require_once $this->trails_root.'/models/OCModel.php';

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

        $allseries = OCSeriesModel::getAllSeries();
        $user_id = $GLOBALS['auth']->auth['uid'];

        if ($perm->have_perm('root')) {
            $this->render_text(json_encode($allseries));
        } else {
            $user_series = OCModel::getUserSeriesIDs($user_id);
            $u_seriesids = array();
            $u_series    = array();

            foreach ($user_series as $user_serie){
                $u_seriesids[] = $user_serie['series_id'];
            }

            foreach ($allseries as $serie) {
                if (in_array($serie['identifier'], $u_seriesids)){
                    $u_series[] = $serie;
                }
            }

            $this->render_text(json_encode($u_series));
        }
    }

    function getepisodes_action($series_id)
    {

        $search_client = SearchClient::getInstance(OCRestClient::getCourseIdForSeries($series_id));
        $episodes      = $search_client->getEpisodes($series_id);
        $result        = [];

        if (!is_array($episodes)) {
            $episodes = [$episodes];
        }

        foreach ($episodes as $episode) {
            if (key_exists('mediapackage', $episode)){
                $result[] = $episode;
            }
        }

        $this->render_text(json_encode($result));
    }

    /**
     * @param $workflow_id
     * @throws Exception
     * @throws Trails_DoubleRenderError
     */
    function getWorkflowStatus_action($workflow_id){
        $this->workflow_client = WorkflowClient::getInstance(OCRestClient::getCourseIdForWorkflow($workflow_id));
        $resp = $this->workflow_client->getWorkflowInstance($workflow_id);
        $this->render_text(json_encode($resp));

    }

    function getWorkflowStatusforCourse_action($course_id)
    {
        $workflow_ids = OCModel::getWorkflowIDsforCourse($course_id);
        $this->workflow_client = WorkflowClient::getInstance($course_id);
        if (!empty($workflow_ids)) {
            $states = OCModel::getWorkflowStates($course_id, $workflow_ids);

            $this->render_text(json_encode($states));
        } else {
            $this->render_nothing();
        }
    }

}
