<?php

class DropObsoleteConfigFields extends Migration
{
    public function description()
    {
        return 'Remove obsolete config fields upload and schedule which reside now in their own table';
    }

    public function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'oc_config'
        ");
        $stmt->execute();
        $foreign_keys = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($foreign_keys as $fk_name) {
            $db->exec("ALTER TABLE `oc_config` DROP FOREIGN KEY `{$fk_name}`");
        }

        $db->exec("ALTER TABLE `oc_config`
            DROP `upload`,
            DROP `schedule`
        ");
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_config`
            ADD COLUMN `upload` int(11) DEFAULT NULL,
            ADD COLUMN `schedule` int(11) DEFAULT NULL
        ");

        $db->exec("ALTER TABLE `oc_config`
            ADD KEY `upload` (`upload`),
            ADD KEY `schedule` (`schedule`),
            ADD CONSTRAINT `oc_config_ibfk_1` FOREIGN KEY (`upload`) REFERENCES `oc_workflow_config` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD CONSTRAINT `oc_config_ibfk_2` FOREIGN KEY (`schedule`) REFERENCES `oc_workflow_config` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }
}