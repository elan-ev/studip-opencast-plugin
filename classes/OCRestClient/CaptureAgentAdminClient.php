<?php
    require_once "OCRestClient.php";
    class CaptureAgentAdminClient extends OCRestClient
    {
        static $me;
        function __construct() {
            if ($config = parent::getConfig('captureadmin')) {
                parent::__construct($config['service_url'],
                                    $config['service_user'],
                                    $config['service_password']);
            } else {
                throw new Exception (_("Die Capture-Agent-Adminservice Konfiguration wurde nicht im g�ltigen Format angegeben."));
            }
        }

        /**
         *  getCaptureAgents() - retrieves a representation of all Capture Agents from conntected Opencast-Matterhorn Core
         *
         *	@return array string response of connected Capture Agents
         */
        function getCaptureAgentsXML() {
            // URL for Matterhorn 1.1
            // TODO: USE JSON-based Service instead of XML (available since OC Matterhorn 1.2)
            $service_url = "/agents";
            if($response = $this->getXML($service_url)){
                // deal with NS struggle of Matterhorn 1.1 since we cannot deal with json responses there...
               $needle = array('<ns1:agent-state-updates xmlns:ns1="http://capture.admin.opencastproject.org">',
                                '<ns1:agent-state-update xmlns:ns1="http://capture.admin.opencastproject.org">',
                                '</ns1:agent-state-update>',
                                '</ns1:agent-state-updates>');

                $replacements = array('<agent-state-updates>','<agent-state-update>','</agent-state-update>','</agent-state-updates>');
                $xml = simplexml_load_string(str_replace($needle, $replacements, $response));
                $json = json_encode($xml);
                $agent_repsonse = json_decode($json,TRUE);
                return $agent_repsonse['agent-state-update'];
            } else return false;
        }

        function getCaptureAgents() {
            $service_url = "/agents.json";
            if($agents = $this->getJSON($service_url)){
                return $agents;
            } else return false;
        }
        }
?>