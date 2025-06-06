<?php

namespace Opencast\Models\REST;
use Opencast\Models\Config;

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
     * Returns all available workflow definitions
     *
     * @return array|boolean Workflow Instances
     */
    public function getDefinitions()
    {
        $response = $this->opencastApi->workflow->getDefinitions();

        if ($response['code'] == 200) {
            if (isset($response['body']->definitions->definition) && !empty($response['body']->definitions->definition)) {
                return $response['body']->definitions->definition;
            }
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
        if (!empty($wf_defs)) {
            foreach ($wf_defs as $wf_def) {
                if (!empty($wf_def->tags)) {
                    if (is_array($wf_def->tags->tag)) {
                        foreach ($wf_def->tags->tag as $tag) {
                            $tagged_wfs[] = array(
                                'id'          => $wf_def->id,
                                'title'       => $wf_def->title,
                                'description' => $wf_def->description,
                                'tag'        => $tag
                            );
                        }
                    }
                    else {
                        $tagged_wfs[] = array(
                            'id'          => $wf_def->id,
                            'title'       => $wf_def->title,
                            'description' => $wf_def->description ?? null,
                            'tag'         => $wf_def->tags->tag ?? null
                        );
                    }
                }
            }
        }

        return $tagged_wfs;
    }
}
