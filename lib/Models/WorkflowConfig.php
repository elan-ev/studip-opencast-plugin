<?php

namespace Opencast\Models;

class WorkflowConfig extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_workflow_config';

        parent::configure($config);
    }

    public static function createForConfigId($config_id) {
        foreach (self::getTypes() as $type) {
            $entry = new self();
            $entry->setValue('config_id', $config_id);
            $entry->setValue('used_for', $type);
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
