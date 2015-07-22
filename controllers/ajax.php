<?php

require_once 'app/controllers/studip_controller.php';
require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/WorkflowClient.php';
require_once $this->trails_root.'/models/OCModel.php';

class AjaxController extends StudipController
{

    function before()
    {
        // notify on trails action
        $klass = substr(get_called_class(), 0, -10);
        $name = sprintf('oc_embed.performed.%s_%s', $klass, $action);
        NotificationCenter::postNotification($name, $this);
    }

    function index_action()
    {
        $this->render_text(_("Ups.."));
    }
    
    function getSeries_action() {

        $allseries = OCSeriesModel::getAllSeries();
        global $perm;
        $user_id = $GLOBALS['auth']->auth['uid'];
        if($perm->have_perm('root')) {
            $this->render_text(json_encode($allseries));
        } else {
            $user_series = OCModel::getUserSeriesIDs($user_id);
            $u_seriesids = array();
            $u_series = array();
            foreach($user_series as $user_serie){
                $u_seriesids[] = $user_serie['series_id'];
            }
            foreach($allseries as $serie) {
                if(in_array($serie['identifier'], $u_seriesids)){
                    $u_series[] = $serie;
                }
            }
            $this->render_text(json_encode($u_series));
        }
        
        
    }
    
    function getEpisodes_action($series_id) {
        
        $search_client = SearchClient::getInstance();
        $episodes = $search_client->getEpisodes($series_id);
    
        $result = array();
        
        foreach($episodes as $episode) {
            if(key_exists('mediapackage', $episode)){
                $result[] = $episode;
            } 
        }
        $this->render_text(json_encode($result));
        
    }
    
    function setEpisodeOrdersForCourse_action() {
        $positions =  Request::getArray('positions');
        foreach($positions  as $position_item) {
            OCModel::setCoursePositionForEpisode($position_item['episode_id'], $position_item['position'], 
                    $position_item['course_id'], $position_item['visibility']);
        }
        $this->render_nothing();
    }

    /**
     * @param $workflow_id
     * @throws Exception
     * @throws Trails_DoubleRenderError
     */
    function getWorkflowStatus_action($workflow_id){
        $this->workflow_client = WorkflowClient::getInstance();
        $resp = $this->workflow_client->getWorkflowInstance($workflow_id);
        $this->render_text(json_encode($resp));

    }

    function getWorkflowStatusforCourse_action($course_id){
        $workflow_ids = OCModel::getWorkflowIDsforCourse($course_id);
        $this->workflow_client = WorkflowClient::getInstance();
        if(!empty($workflow_ids)){
            foreach($workflow_ids as $workflow_id) {
                $resp = $this->workflow_client->getWorkflowInstance($workflow_id['workflow_id']);
                if($resp->state == 'SUCCEEDED') {
                    $states[$workflow_id['workflow_id']] = $resp->state;
                    OCModel::removeWorkflowIDforCourse($workflow_id['workflow_id'], $this->course_id);
                } else $states[$workflow_id['workflow_id']] = $resp;
            }
            $this->render_text(json_encode($states));
        } else $this->render_nothing();
    }

}
