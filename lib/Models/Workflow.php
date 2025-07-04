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
                foreach ($db_workflows as $key => $db_workflow) {
                    if ($db_workflow['name'] === $wf_def['id'] && $db_workflow['tag'] === $wf_def['tag']) {
                        $entry = self::findOneBySql('config_id = ? AND name = ? AND tag = ?', [$config_id, $wf_def['id'], $wf_def['tag']]);
                        $entry->setValue('displayname', $wf_def['title']);
                        $entry->store();

                        $found = true;
                        unset($db_workflows[$key]);
                        break;
                    }
                }
                if (!$found) {
                    $new_entry = new self();
                    $new_entry->setValue('config_id', $config_id);
                    $new_entry->setValue('name', $wf_def['id']);
                    $new_entry->setValue('tag', $wf_def['tag']);
                    $new_entry->setValue('displayname', $wf_def['title']);

                    $settings = [];
                    if ($new_entry->tag == 'upload') {
                        $settings['upload_file_types'] = self::DEFAULT_UPLOAD_FILE_TYPES;
                    }
                    $new_entry->setValue('settings', []);

                    $new_entry->store();
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
}
