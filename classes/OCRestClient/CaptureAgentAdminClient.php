<?php

use Opencast\Models\OCConfig;

class CaptureAgentAdminClient extends OCRestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'CaptureAgentAdminClient';

        if ($config = OCConfig::getConfigForService('capture-admin', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception(_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    public function getCaptureAgents()
    {
        $service_url = "/agents.json";

        if ($agents = $this->getJSON($service_url)) {
            return $this->sanitizeAgents($agents);
        }

        return false;
    }

    public function getCaptureAgentCapabilities($agent_name)
    {
        $service_url = "/agents/" . $agent_name . "/capabilities.json";
        if ($agent = $this->getJSON($service_url)) {
            $x = 'properties-response';
            return $agent->$x->properties->item;
        } else {
            return false;
        }
    }

    private function sanitizeAgents($agents)
    {
        if (is_array($agents->agents->agent)) {
            return $agents->agents->agent;
        }

        return [$agents->agents->agent];
    }
}
