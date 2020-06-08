<?php

namespace Opencast\Models\REST;

class CaptureAgentAdminClient extends RestClient
{
    public static $me;

    function __construct($config_id = 1)
    {
        $this->serviceName = 'CaptureAgentAdminClient';

        parent::__construct($config_id, 'capture-admin');
    }

    /**
     *  getCaptureAgents() - retrieves a representation of all Capture Agents from conntected Opencast-Matterhorn Core
     *
     *  @return array string response of connected Capture Agents
     */
    public function getCaptureAgentsXML()
    {
        // URL for Matterhorn 1.1
        // TODO: USE JSON-based Service instead of XML (available since OC Matterhorn 1.2)

        $service_url = "/agents.xml";

        // deal with NS struggle of Matterhorn 1.1 since we cannot deal with json responses there...
        $needle = [
            '<ns1:agent-state-updates xmlns:ns1="http://capture.admin.opencastproject.org">',
            '<ns1:agent-state-update xmlns:ns1="http://capture.admin.opencastproject.org">',
            '</ns1:agent-state-update>',
            '</ns1:agent-state-updates>'
        ];

        $replacements = array('<agent-state-updates>','<agent-state-update>','</agent-state-update>','</agent-state-updates>');
        $xml = simplexml_load_string(str_replace($needle, $replacements, $response));
        $json = json_encode($xml);
        $agent_repsonse = json_decode($json, true);

        return $agent_repsonse['agent-state-update'];
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
