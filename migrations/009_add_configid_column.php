<?php

class AddConfigidColumn extends Migration {

    function up()
    {
        $db = DBManager::get();

        $result = $db->query("SHOW INDEXES FROM oc_config WHERE Key_name = 'PRIMARY'");

        if (!empty($result->fetchAll())) {
            $db->query("ALTER TABLE `oc_config` DROP PRIMARY KEY, ADD UNIQUE KEY(service_url,service_user,service_password);");
            $db->query("ALTER TABLE `oc_config` ADD `config_id` INT UNIQUE KEY NOT NULL AUTO_INCREMENT FIRST;");
            $db->query("ALTER TABLE `oc_endpoints` ADD `config_id` INT NOT NULL DEFAULT 1 FIRST;");
            $db->query("ALTER TABLE `oc_resources` ADD `config_id` INT NOT NULL DEFAULT 1 FIRST;");
            $db->query("ALTER TABLE `oc_seminar_series` ADD `config_id` INT NOT NULL DEFAULT 1 FIRST;");
            $db->query("ALTER TABLE `oc_seminar_workflows` ADD `config_id` INT NOT NULL DEFAULT 1 FIRST;");

            $db->query("UPDATE oc_resources SET config_id = 2");
            $db->query("UPDATE oc_seminar_series SET config_id = 2, schedule = 0");
            $db->query("UPDATE oc_seminar_workflows SET config_id = 2");
        }
    }

    function down()
    {
        $db = DBManager::get();

        $db->query("ALTER TABLE `oc_config` DROP COLUMN `config_id`;");
        $db->query("ALTER TABLE `oc_config` DROP INDEX `service_url`,  ADD PRIMARY KEY (`service_url`);");
        $db->query("ALTER TABLE `oc_endpoints` DROP COLUMN `config_id`;");
        $db->query("ALTER TABLE `oc_resources` DROP COLUMN `config_id`;");
        $db->query("ALTER TABLE `oc_seminar_series` DROP COLUMN `config_id`;");
        $db->query("ALTER TABLE `oc_seminar_workflows` DROP COLUMN `config_id`;");

    }
}
