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
     * getWorkflowInstance - Get a specific workflow instance
     *
     * @param $id The workflow instance identifier
     *
     * @return $result A JSON representation of a workflow instance
     */
    public function getWorkflowInstance($id)
    {
        $service_url = "/instance/{$id}.json";
        if ($result = $this->getJSON($service_url)) {
            return $result->workflow;
        }

        return false;
    }

    /**
     * getInstances() - returns all Workflow instances for a given SeriesID
     *
     * @return array Workflow Instances
     */
    public function getRunningInstances($seriesID)
    {
        $service_url = sprintf("/instances.json?state=&q=&seriesId=%s&seriesTitle=&creator=&contributor=&fromdate=&todate=&language="
            . "&license=&title=&subject=&workflowdefinition=&mp=&op=&sort=&startPage=0&count=1000&compact=true", $seriesID);

        $ret = [];
        $instances = $this->getJSON($service_url);
        if ($instances && !empty($instances->workflows->workflow) ) {
            foreach ($instances->workflows->workflow as $wf) {
                if ($wf->state == 'RUNNING') {
                    $ret[$wf->mediapackage->id] = $wf;
                }
            }
        }
        return $ret;
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

    public function removeInstanceComplete($id)
    {
        $result      = $this->deleteJSON("/remove/{$id}", true);
        if (in_array($result[1], [204, 404])) {
            return true;
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
