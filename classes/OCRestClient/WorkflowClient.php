<?php

use Opencast\Models\OCConfig;

class WorkflowClient extends OCRestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'WorkflowClient';

        if ($config = OCConfig::getConfigForService('workflow', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * getDefinitions() - returns all Workflow definitions
     *
     * @return array Workflow Instances
     */
    public function getDefinitions()
    {
        $service_url = sprintf("/definitions.json");

        if ($definitions = $this->getJSON($service_url)) {
            return $definitions;
        }

        return false;
    }

    ####################
    # HELPER FUNCTIONS #
    ####################

    /**
     * getTaggedWorkflowDefinitions() - returns a revised collection of all tagged Workflow definitions
     *
     * @return array tagged Workflow Instances
     */
    public function getTaggedWorkflowDefinitions()
    {
        $wf_defs = self::getDefinitions();

        $tagged_wfs = [];

        if (!empty($wf_defs->definitions->definition)) {
            foreach ($wf_defs->definitions->definition as $wdef) {
                if (is_array($wdef->tags->tag)) {
                    $tagged_wfs[] = [
                        'id'          => $wdef->id,
                        'title'       => $wdef->title,
                        'description' => $wdef->description,
                        'tags'        => $wdef->tags->tag
                    ];
                }
            }
        }

        return $tagged_wfs;
    }
}
