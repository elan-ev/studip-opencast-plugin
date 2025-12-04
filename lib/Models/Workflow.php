<?php

namespace Opencast\Models;

use Opencast\Models\REST\WorkflowClient;

class Workflow extends \SimpleORMap
{
    protected const allowed_settings_fields = [
        'upload_file_types'
    ];

    public const DEFAULT_UPLOAD_FILE_TYPES = '.avi,.mkv,.mp4,.mpeg,.webm,.mov,.ogg,.ogv,.flv,.f4v,.wmv,.asf,.mpg,.mpeg,.ts,.3gp,.3g2,video/mp4,video/x-m4v,video/webm,video/ogg,video/mpeg,video/*';

    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_workflow';

        $config['serialized_fields']['settings'] = 'JSONArrayObject';
        $config['registered_callbacks']['after_initialize'][] = 'sanitizeSettings';
        $config['registered_callbacks']['before_store'][]     = 'sanitizeSettings';

        parent::configure($config);
    }

    /**
     * Synchronizes workflow definitions from Opencast with the local database for a given configuration.
     *
     * This method fetches all workflow definitions from the Opencast instance associated with the provided
     * configuration ID. It updates existing workflow entries, adds new ones if they do not exist, and removes
     * any workflows from the local database that are no longer present in Opencast.
     *
     * @param string|int $config_id The ID of the Opencast configuration to synchronize workflows for.
     * @return void
     */
    public static function updateWorkflowsByConfigId($config_id)
    {
        $db_workflows = [];
        try {
            $wf_client = WorkflowClient::getInstance($config_id);
            $wf_defs = $wf_client->getTaggedWorkflowDefinitions();

            // Avoid further process in case the oc is not responsive!
            if ($wf_defs === false) {
                return;
            }

            $db_workflows = self::findBySql('config_id = ?', [$config_id]);

            foreach ($wf_defs as $wf_def) {
                $found = false;
                // We need to make sure, that we have [] as for the empty config panels instead of empty string!
                $wf_config_panel_filtered =
                    !empty($wf_def['configuration_panel_json']) ? $wf_def['configuration_panel_json'] : '[]';

                // Usually the config panels come with indented json string,
                // with decoding and encoding it again, we trim that indentation.
                $wf_config_panel_filtered = json_encode(json_decode($wf_config_panel_filtered));

                foreach ($db_workflows as $key => $db_workflow) {
                    if ($db_workflow['name'] === $wf_def['identifier'] && $db_workflow['tag'] === $wf_def['tag']) {
                        $entry = self::findOneBySql('config_id = ? AND name = ? AND tag = ?',
                            [$config_id, $wf_def['identifier'], $wf_def['tag']]);
                        $entry->setValue('displayname', $wf_def['title']);
                        // In case WF is already there, we also update the config panel json.
                        $entry->setValue('configuration_panel_json', $wf_config_panel_filtered);

                        $entry->store();

                        // Take care of config panel options.
                        self::configPanelOptionsMapper($entry);

                        $found = true;
                        unset($db_workflows[$key]);
                        break;
                    }
                }
                if (!$found) {
                    $new_entry = new self();
                    $new_entry->setValue('config_id', $config_id);
                    $new_entry->setValue('name', $wf_def['identifier']);
                    $new_entry->setValue('tag', $wf_def['tag']);
                    $new_entry->setValue('displayname', $wf_def['title']);
                    $new_entry->setValue('configuration_panel_json', $wf_config_panel_filtered);

                    $settings = [];
                    if ($new_entry->tag == 'upload') {
                        $settings['upload_file_types'] = self::DEFAULT_UPLOAD_FILE_TYPES;
                    }
                    $new_entry->setValue('settings', []);

                    $new_entry->store();

                    self::configPanelOptionsMapper($new_entry);
                }
            }
        } catch (\Throwable $th) {
        }

        // the remaining entries or workflow - tag combinations that are not there anymore and need to be deleted from Stud.IP
        if (!empty($db_workflows)) {
            foreach ($db_workflows as $db_workflow) {
                $db_workflow->delete();
            }
        }
    }

    public function sanitizeSettings($event)
    {
        if (empty($this->settings)) {
            return;
        }

        foreach ($this->settings as $key => $value) {
            if (in_array($key, self::allowed_settings_fields) === false) {
                unset($this->settings[$key]);
            }
        }
    }

    /**
     * Update settings of workflows
     *
     * @param string $config_id related config to ensure correct oc service
     * @param array $workflow_settings Array of workflow settings to update
     * @return void
     */
    public static function updateSettings($config_id, $workflow_settings)
    {
        foreach ($workflow_settings as $wf_id => $wf_setting) {
            // Ensure same config is modified
            $workflow = self::findOneBySQL('id = ? AND config_id = ?', [$wf_id, $config_id]);

            if (!empty($workflow)) {
                $workflow->settings = $wf_setting;
                $workflow->store();
            }
        }
    }

    /**
     * @inheritDoc
     *
     * As we call this in SimpleConfig to feed the workflows to the frontend,
     * we need to overwrite it to convert the config panel properly.
     */
    public function toArray($only_these_fields = null)
    {
        $ret = parent::toArray($only_these_fields);

        // We then convert the config panel json string to array.
        $config_panel_json_string = !empty($ret['configuration_panel_json']) ? $ret['configuration_panel_json'] : '[]';
        $configuration_panel_options = !empty($ret['configuration_panel_options']) ? $ret['configuration_panel_options'] : '[]';
        $ret['configuration_panel_json'] = json_decode($config_panel_json_string, true) ?? [];
        $ret['configuration_panel_options'] = json_decode($configuration_panel_options, true) ?? [];

        return $ret;
    }

    private static function configPanelOptionsMapper($entry)
    {
        $existing_options = json_decode($entry->configuration_panel_options, true) ?? [];
        $config_panel = json_decode($entry->configuration_panel_json, true) ?? [];
        $eligible_fieldset = [];

        if (!empty($config_panel)) {
            foreach ($config_panel as $item) {
                $description = $item['description'] ?? '';
                if (!empty($item['fieldset'])) {
                    foreach ($item['fieldset'] as $cp_item) {
                        $name = $cp_item['name'];
                        $eligible_fieldset[] = $name;
                        $label = $cp_item['label'] ?? $description;
                        $show = true;
                        if (!isset($existing_options[$name])) {
                            $existing_options[$name] = [
                                'displayName' => [
                                    'default' => $label,
                                ],
                                'show' => $show
                            ];
                        }
                    }
                }
            }
        }

        $to_remove_options = array_diff_key($existing_options, array_flip($eligible_fieldset));
        foreach ($to_remove_options as $key => $option) {
            unset($existing_options[$key]);
        }

        $entry->setValue('configuration_panel_options', json_encode($existing_options));
        $entry->store();
    }

    /**
     * Update configuration panel options of workflows
     *
     * @param string $config_id related config to ensure correct oc service
     * @param array $config_panel_options Array of configuration panel options to update
     * @return void
     */
    public static function updateConfigPanelOptions($config_id, $config_panel_options)
    {
        foreach ($config_panel_options as $wf_id => $config_panel_options) {
            // Ensure same config is modified
            $workflow = self::findOneBySQL('id = ? AND config_id = ?', [$wf_id, $config_id]);

            if (!empty($workflow)) {
                $workflow->configuration_panel_options = json_encode($config_panel_options);
                $workflow->store();
            }
        }
    }
}
