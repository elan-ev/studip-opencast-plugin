<?php
namespace Opencast\Models\REST;

use Opencast\Models\Config;

class ApiWorkflowsClient extends RestClient
{
    public static $me;
    public $serviceName = "ApiWorkflows";

    function __construct($config_id = 1)
    {
        if ($config = Config::getConfigForService('apiworkflows', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * Perform the "retract" workflow for an episode
     * 
     * @param string $episode_id episode id
     * 
     * @return boolean the status of the performed workflow
     */
    public function retract($episode_id)
    {
        $response = $this->opencastApi->workflowsApi->run($episode_id, 'retract');
        
        if ($response['code'] == 201) {
            return true;
        }
        return false;
    }

    /**
     * Republish the passed episode / event
     *
     * @param string $episode_id episode id
     *
     * @return boolean true, if the workflow could be started, false if an error occured
     * or a workflow was already in process
     */
    public function republish($episode_id)
    {
        $response = $this->opencastApi->workflowsApi->run($episode_id, 'republish-metadata');

        if ($response['code'] == 201) {
            return true;
        }
        return false;
    }
}
