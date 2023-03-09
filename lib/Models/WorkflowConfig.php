<?php

namespace Opencast\Models;

use Opencast\Models\Workflow;
use Opencast\Models\Config;

class WorkflowConfig extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_workflow_config';

        parent::configure($config);
    }

    public static function createAndUpdateAll($workflows=null) {
        $configs = Config::findBySql(1);
        foreach ($configs as $config) {
            self::createAndUpdateByConfigId($config['id'], $workflows);
        }
    }

    public static function createAndUpdateByConfigId($config_id, $workflows=null) {
        Workflow::updateWorkflowsByConfigId($config_id);

        foreach (self::getTypes() as $type) {
            $entry = self::findOneBySql('config_id = ? AND used_for = ?', [$config_id, $type]);
            if (!isset($entry)) {
                $entry = new self();
                $entry->setValue('config_id', $config_id);
                $entry->setValue('used_for', $type);
            }
            
            $type = ($type == 'studio') ? 'upload' : $type;
            $workflow_id = isset($workflows[$entry->id]) ? $workflows[$entry->id]['workflow_id'] : $entry->workflow_id;
            if (Workflow::countBySql('config_id = ? AND tag = ? AND id = ?', [$config_id, $type, $workflow_id]) > 0) {
                $entry->setValue('workflow_id', $workflow_id);
            }
            else {
                $workflow = Workflow::findOneBySql('config_id = ? AND tag = ?', [$config_id, $type]);
                if (isset($workflow)) {
                    $entry->setValue('workflow_id', $workflow->id);
                }
                else {
                    $entry->setValue('workflow_id', null);
                }
            }
            $entry->store();
        }
    }

    private static function getTypes() {
        $stmt = \DBManager::get()->prepare("SHOW COLUMNS FROM `oc_workflow_config` LIKE 'used_for'");
        $stmt->execute();
        preg_match("/^enum\(\'(.*)\'\)$/", $stmt->fetchAll()[0]['Type'], $matches);
        $types = explode("','", $matches[1]);
        return $types;
    }
}
