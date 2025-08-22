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
     * Returns all available workflow definitions.
     *
     * Note: by default we also get the configuration panel json
     *
     * @param array $params Optional parameters:
     *                      - 'withoperations' (bool): Include workflow operations in the response.
     *                      - 'withconfigurationpanel' (bool): Include configuration panel.
     *                      - 'withconfigurationpaneljson' (bool): Include configuration panel in JSON format.
     *                      - 'sort' (array): Associative array for sorting, e.g. ['title' => 'DESC'].
     *                      - 'limit' (int): Maximum number of results to return.
     *                      - 'offset' (int): Index of the first result to return.
     *                      - 'filter' (array): Associative array for filtering,
     *                              e.g. ['tag' => '{Workflow definitions where the tag is included}'].
     * @return array|bool Array of workflow definitions, or false if an error occurs.
     */
    public function getDefinitions($params = [])
    {
        $default_params = [
            'withoperations' => false,
            'withconfigurationpaneljson' => true,
            'withconfigurationpanel' => false,
        ];

        $params = array_merge($default_params, $params);

        $response = $this->opencastApi->workflowsApi->getAllDefinitions($params);
        if ($response['code'] == 200) {
            return $response['body'] ?: [];
        }

        // If the response is not 200, we return false to indicate an error.
        return false;
    }


    /**
     * Retrieves a specific workflow definition by its ID.
     *
     * @param string $workflow_definition_id The ID of the workflow definition to retrieve.
     * @param array $params Optional parameters:
     *                      - 'withoperations' (bool): Include workflow operations in the response.
     *                      - 'withconfigurationpaneljson' (bool): Include configuration panel in JSON format.
     *                      - 'withconfigurationpanel' (bool): Include configuration panel.
     * @return array The workflow definition.
     */
    public function getDefinition($workflow_definition_id, $params = [])
    {
        $with_operations = isset($params['withoperations']) ?
            (bool) $params['withoperations'] : false;
        $with_configuration_panel_json = isset($params['withconfigurationpaneljson']) ?
            (bool) $params['withconfigurationpaneljson'] : false;
        $with_configuration_panel = isset($params['withconfigurationpanel']) ?
            (bool) $params['withconfigurationpanel'] : false;

        $response = $this->opencastApi->workflowsApi->getDefinition(
            $workflow_definition_id,
            $with_operations,
            $with_configuration_panel_json,
            $with_configuration_panel
        );
        if ($response['code'] == 200) {
            return $response['body'] ?: [];
        }

        // If the response is not 200, we return false to indicate an error.
        return [];
    }

    ####################
    # HELPER FUNCTIONS #
    ####################

    /**
     * Returns a revised collection of all tagged Workflow definitions
     *
     * @return array|bool tagged Workflow Instances, or false if something goes wrong!
     */
    public function getTaggedWorkflowDefinitions()
    {
        $wf_defs = self::getDefinitions();

        // We break the process if the return result is false, indicating the connection error!
        if ($wf_defs === false) {
            return false;
        }

        $tagged_wfs = [];
        if (!empty($wf_defs)) {
            foreach ($wf_defs as $wf_def) {
                if (!empty($wf_def->tags)) {
                    foreach ($wf_def->tags as $tag) {
                        $tagged_wfs[] = [
                            'identifier' => $wf_def->identifier,
                            'title' => $wf_def->title,
                            'description' => $wf_def->description,
                            'tag' => $tag,
                            'configuration_panel_json' => trim($wf_def->configuration_panel_json ?? '')
                        ];
                    }
                }
            }
        }

        return $tagged_wfs;
    }
}
