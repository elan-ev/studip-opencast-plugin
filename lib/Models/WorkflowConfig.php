<?php

namespace Opencast\Models;

use Opencast\Models\Workflow;
use Opencast\Models\Config;

class WorkflowConfig extends \SimpleORMap
{
    /**
     * @inheritDoc
     */
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_workflow_config';

        parent::configure($config);
    }

    /**
     * Create initial workflow configuration for all oc servers
     *
     * @param [type] $workflows
     *
     * @return void
     */
    public static function initWorkflows()
    {
        $configs = Config::findBySql(1);
        foreach ($configs as $config) {
            self::createAndUpdateByConfigId($config['id']);
        }
    }

    /**
     * Update mapping of used workflows for passed config
     *
     * @param int $config_id
     * @param Array $workflows
     *
     * @return void
     */
    public static function createAndUpdateByConfigId($config_id, $workflows = null)
    {
        if (empty($config_id)) {
            return;
        }

        // var_dump($workflows);

        Workflow::updateWorkflowsByConfigId($config_id);

        foreach (self::getTypes() as $type) {
            $entry = self::findOneBySql('config_id = ? AND used_for = ?', [$config_id, $type]);

            if (!isset($entry)) {
                $entry = new self();
                $entry->setValue('config_id', $config_id);
                $entry->setValue('used_for', $type);
            }

            switch ($type) {
                case 'studio';
                    $type = 'upload';
                    break;

                case 'subtitles';
                    $type = 'archive';
                    break;
            }

            $workflow_id = isset($workflows[$entry->id])
                ? $workflows[$entry->id]['workflow_id']
                : $entry->workflow_id;

            if (Workflow::countBySql('config_id = ? AND tag = ? AND id = ?', [$config_id, $type, $workflow_id]) > 0) {
                $entry->setValue('workflow_id', $workflow_id);
            } else {
                $workflow = Workflow::findOneBySql('config_id = ? AND tag = ?', [$config_id, $type]);
                if (isset($workflow)) {

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
