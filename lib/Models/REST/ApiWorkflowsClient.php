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
     * Republish the passed episode / event
     *
     * @param string $episode_id episode id
     *
     * @return boolean true, if the workflow could be started, false if an error occured
     * or a workflow was already in process
     */
    public function republish($episode_id)
    {
        // TODO: configurable workflow for republishing
        $response = $this->opencastApi->workflowsApi->run($episode_id, 'republish-metadata');

        if ($response['code'] == 201) {
            return true;
        }
        return false;
    }
}
