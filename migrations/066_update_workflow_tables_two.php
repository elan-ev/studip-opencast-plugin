<?php

use Opencast\Models\WorkflowConfig;

class UpdateWorkflowTablesTwo extends Migration
{
    public function description()
    {
        return 'Alter tables for link configurable workflows';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_workflow` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `config_id` int NOT NULL,
            `name` varchar(255),
            `tag` varchar(255),
            `displayname` varchar(255),
            CONSTRAINT oc_workflow_fk FOREIGN KEY (`config_id`) REFERENCES `oc_config` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        );");

        $db->exec("DELETE FROM oc_workflow_config");

        $db->exec("ALTER TABLE `oc_workflow_config`
            Drop COLUMN `displayname`,
            Drop COLUMN `workflow`,
            MODIFY `used_for` enum('schedule','upload','studio','delete') CHARACTER SET latin1 COLLATE latin1_bin,
            ADD COLUMN workflow_id int,
            ADD UNIQUE KEY oc_workflow_config_unique(config_id, used_for),
            ADD CONSTRAINT oc_workflow_config_fk_wf_id FOREIGN KEY (`workflow_id`) REFERENCES `oc_workflow` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ");

        $stmt = $stmt = $db->prepare("SELECT id FROM oc_config;");
        $stmt->execute();
        $configs = $stmt->fetchAll();

        $stmt = DBManager::get()->prepare("SHOW COLUMNS FROM `oc_workflow_config` LIKE 'used_for';");
        $stmt->execute();
        $enum = $stmt->fetchAll()[0]['Type'];
        preg_match("/^enum\(\'(.*)\'\)$/", $enum, $matches);
        $types = explode("','", $matches[1]);

        $stmt = $db->prepare('INSERT IGNORE INTO oc_workflow_config (config_id, used_for, workflow_id)
            VALUES (:config_id, :used_for, null)');

        foreach ($configs as $config) {
            //WorkflowConfig::createOrUpdatebyConfigId($config['id']);     TODO Workflow API fails
            foreach ($types as $type) {
                $stmt->execute([
                    'config_id' => $config['id'],
                    'used_for'  => $type
                ]);
            }
        }

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_workflow_config`
            DROP CONSTRAINT oc_workflow_config_fk_wf_id;
        ");
        $db->exec("ALTER TABLE `oc_workflow_config`
            ADD COLUMN `displayname` varchar(255),
            ADD COLUMN `workflow` varchar(255),
            MODIFY `used_for` enum('schedule','upload','studio') CHARACTER SET latin1 COLLATE latin1_bin,
            DROP COLUMN workflow_id,
            DROP CONSTRAINT oc_workflow_config_unique;
        ");
        $db->exec("DELETE FROM oc_workflow_config");

        $db->exec("ALTER TABLE `oc_workflow`
            DROP CONSTRAINT oc_workflow_fk;
        ");
        $db->exec("DROP TABLE oc_workflow;");

        SimpleOrMap::expireTableScheme();
    }
}