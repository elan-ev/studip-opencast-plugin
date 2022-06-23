<?php

namespace Opencast\Models\REST;

class WorkflowClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'WorkflowClient';

        if ($config = Config::getConfigForService('workflow', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }


    /**
     * Get a specific workflow instance
     *
     * @param string $id The workflow instance identifier
     *
     * @return object|boolean A JSON representation of a workflow instance, or false when unable to get
     */
    public function getWorkflowInstance($id)
    {
        $response = $this->opencastApi->workflow->getInstance($id);

        if ($response['code'] == 200) {
            if (isset($response['body']->workflow)) {
                return $response['body']->workflow;
            }
        }

        return false;
    }

    /**
     * Returns all Workflow instances for a given SeriesID
     *
     * @param string $series_id The series identifier
     * 
     * @return array|boolean Workflow Instances, or false if unable to get
     */
    public function getInstances($series_id = null)
    {
        $params = [
            'count' => 1000,
            'compact' => true
        ];

        if (!empty($series_id)) {
            $params['seriesId'] = $series_id;
        }

        $response = $this->opencastApi->workflow->getInstances($params);

        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Returns all available workflow definitions
     *
     * @return array|boolean Workflow Instances
     */
    public function getDefinitions()
    {
        $response = $this->opencastApi->workflow->getDefinitions();

        if ($response['code'] == 200) {
            if (isset($response['body']->definitions)) {
                return $response['body']->definitions;
            }
        }

        return false;
    }

    /**
     * Removes a workflow instance from connected Opencast
     * 
     * @param string $id the workflow instance id
     * 
     * @return boolean success or not
     */
    public function removeInstanceComplete($id)
    {
        $response = $this->opencastApi->workflow->removeInstance($id);

        if (in_array($response['code'], [204, 404])) {
            return true;
        }

        return false;
    }

     ####################
     # HELPER FUNCTIONS #
     ####################

    /**
     * Returns a revised collection of all tagged Workflow definitions
     *
     * @return array tagged Workflow Instances
     */
    public function getTaggedWorkflowDefinitions()
    {
        $wf_defs = self::getDefinitions();

        $tagged_wfs = array();

        if (!empty($wf_defs->definitions->definition)) {
            foreach ($wf_defs->definitions->definition as $wdef) {
                if (is_array($wdef->tags->tag)) {
                    $tagged_wfs[] = array(
                        'id'          => $wdef->id,
                        'title'       => $wdef->title,
                        'description' => $wdef->description,
                        'tags'        => $wdef->tags->tag
                    );
                }
            }
        }

        return $tagged_wfs;
    }
}
