<?php
    require_once "OCRestClient.php";
    class CaptureAgentAdminClient extends OCRestClient
    {
        static $me;
        function __construct() {
            $this->serviceName = 'CaptureAgentAdminClient';
            try {
                if ($config = parent::getConfig('capture-admin')) {
                    parent::__construct($config['service_url'],
                                        $config['service_user'],
                                        $config['service_password']);
                } else {
                    throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
                }
            } catch(Exception $e) {
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
            $service_url = "/agents.xml";
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
                //} 
            
            
        //     return false;
        }

        function getCaptureAgents() {
            $service_url = "/agents.json";
            if($agents = $this->getJSON($service_url)){
                $sanitzed_agents = $this->sanitizeAgents($agents);
                return $sanitzed_agents;
            } else return false;
        }
        
        function getCaptureAgentCapabilities($agent_name) {
            $service_url = "/agents/" . $agent_name . "/capabilities.json";
            if($agent = $this->getJSON($service_url)) {
                $x = 'properties-response';
                return $agent->$x->properties->item;
            } else return false;
        }

        private function sanitizeAgents($agents){
            if(is_array($agents->agents->agent)) $sanitized_agents = $agents->agents->agent;
            else $sanitized_agents = $agents->agents;

            return $sanitized_agents;
        }
    }

?>