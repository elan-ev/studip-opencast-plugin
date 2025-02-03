<?php

class InitOcplugin extends Migration {
    function up() {


        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_config` (
              `service_url` varchar(255) NOT NULL,
              `service_user` varchar(255) NOT NULL,
              `service_password` varchar(255) NOT NULL,
              PRIMARY KEY (`service_url`)
              )  ROW_FORMAT=DYNAMIC;");

        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_seminar_series` (
                `seminar_id` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL ,
                `series_id` VARCHAR( 64 ) NOT NULL ,
                `visibility` ENUM(  'visible',  'invisible' )NOT NULL ,
                `schedule` TINYINT( 1 ) NOT NULL DEFAULT '0',
                PRIMARY KEY (  `seminar_id` ,  `series_id` )
                );");

        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_resources` (
                `resource_id` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL ,
                `capture_agent` VARCHAR( 64 ) NOT NULL ,
                PRIMARY KEY (  `resource_id` ,  `capture_agent` )
                );");

        if (StudipVersion::newerThan('4.4'))
        {
            DBManager::get()->query("INSERT INTO `resource_property_definitions`
                (`property_id`, `name`, `description`, `type`, `options`, `system`)
                VALUES (MD5('" . uniqid() . "'), 'Opencast Capture Agent', '', 'bool', 'vorhanden', 0)");
        }
        else
        {
            DBManager::get()->query("INSERT INTO `resources_properties`
                (`property_id`, `name`, `description`, `type`, `options`, `system`)
                VALUES (MD5('".uniqid()."'), 'Opencast Capture Agent', '', 'bool', 'vorhanden', 0)");
        }

        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_seminar_episodes` (
                `seminar_id` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL ,
                `episode_id` VARCHAR( 64 ) NOT NULL ,
                `visible` ENUM( 'true', 'false' ) NOT NULL DEFAULT 'true',
                PRIMARY KEY ( `seminar_id` , `episode_id` )
                );");


        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_scheduled_recordings` (
                `seminar_id` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL ,
                `series_id` VARCHAR( 64 ) NOT NULL ,
                `date_id` VARCHAR( 32 ) NOT NULL ,
                `resource_id` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL ,
                `capture_agent` VARCHAR( 64 ) NOT NULL ,
                `event_id` VARCHAR( 64 ) NOT NULL,
                `status` ENUM( 'scheduled', 'recorded' ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL ,
                PRIMARY KEY ( `seminar_id` , `series_id` , `date_id` , `resource_id` , `capture_agent` )
                );");


                 DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_endpoints` (
                   `service_url` varchar(255) NOT NULL,
                   `service_host` varchar(255) NOT NULL DEFAULT '',
                   `service_type` varchar(255) NOT NULL DEFAULT '',
                   PRIMARY KEY (`service_url`)
                 )  ROW_FORMAT=DYNAMIC;");




    }

    function down()
    {
        $db = DBManager::get();

        // clean out the whole oc config
        $contents = file_get_contents(__DIR__ . '/../tools/completely_remove_opencast_tables_and_settings.sql');

        $statements = preg_split("/;[[:space:]]*\n/", $contents, -1, PREG_SPLIT_NO_EMPTY);


        foreach ($statements as $statement) {
            $db->exec($statement);
        }
    }
}
