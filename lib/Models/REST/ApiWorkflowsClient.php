<?php

use Opencast\Models\OCConfig;

class ApiWorkflowsClient extends OCRestClient
{
    public static $me;
    public $serviceName = "ApiWorkflows";

    function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('apiworkflows', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    public function retract($episode_id)
    {
        $service_url = '/';
        $data = [
            'event_identifier' => $episode_id,
            'workflow_definition_identifier' => 'retract',
        ];
        list(, $code) = $this->postJSON($service_url, $data, true);

        if ($code == 201) {
            return true;
        }

        return false;
    }

    /**
     * Republish the passed episode / event
     *
     * @param  string $episode_id
     *
     * @return int   true, if the workflow could be started, false if an error occured
     *               or a workflow was already in process
     */
    public function republish($episode_id)
    {
        $service_url = "/";

        $data = [
            'event_identifier'               => $episode_id,
            'workflow_definition_identifier' => 'republish-metadata',
            'configuration'                  => '',
            'withoperations'                 => false,
            'withconfiguration'              => false
        ];

        $result = $this->postJSON($service_url, $data, true);

        if ($result[1] == 201) {
            return true;
        }

        return false;
    }
}
