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
            $items = $agent->$x->properties->item;
            // https://github.com/orgs/opencast/discussions/6988
            if (isset($items) && is_object($items) && !is_array($items)) {
                $items = [$items];
            }
            return $items;
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
