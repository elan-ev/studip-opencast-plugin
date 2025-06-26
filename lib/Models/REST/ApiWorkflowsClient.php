<?php
namespace Opencast\Models\REST;

class ApiWorkflowsClient extends RestClient
{
    public static $me;

    function __construct($config_id = 1)
    {
        $this->serviceName = 'ApiWorkflows';
        $this->serviceType = 'apiworkflows';
        $config = $this->getConfigForClient($config_id);
        parent::__construct($config);
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
