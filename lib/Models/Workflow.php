<?php

namespace Opencast\Models;

use Opencast\Models\REST\WorkflowClient;

class Workflow extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_workflow';

        parent::configure($config);
    }

    public static function updateWorkflows() {
        $configs = Config::findBySql(1);
        foreach ($configs as $config) {

            try {
                $wf_client = WorkflowClient::getInstance($config->id);
                $wf_defs = $wf_client->getTaggedWorkflowDefinitions();

                $db_workflows = self::findBySql('config_id = ?', [$config->id]);

                foreach ($wf_defs as $wf_def) {
                    $found = false;
                    foreach ($db_workflows as $db_workflow) {
                        if ($db_workflow['name'] === $wf_def['id'] && $db_workflow['tag'] === $wf_def['tag']) {
                            $entry = self::findOneBySql('config_id = ? AND name = ? AND tag = ?', [$config->id, $wf_def['id'], $wf_def['tag']]);
                            $entry->setValue('displayname', $wf_def['title']);
                            $entry->store();

                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $new_entry = new self();
                        $new_entry->setValue('config_id', $config->id);
                        $new_entry->setValue('name', $wf_def['id']);
                        $new_entry->setValue('tag', $wf_def['tag']);
                        $new_entry->setValue('displayname', $wf_def['title']);
                        $new_entry->store();
                    }
                }
            } catch (\Throwable $th) {
            }
        }
    }
}