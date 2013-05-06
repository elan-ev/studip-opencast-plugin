<?php

class InitOcplugin extends Migration {
    function up() {
        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_series` (
                  `series_id` VARCHAR( 64 ) NOT NULL ,
                   `seminars` INT( 11 ) NULL ,
                   PRIMARY KEY ( `series_id` )
                   ) ENGINE = MYISAM;");
         DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_config` (
                  `service_type` ENUM('annotation','caption','capture-admin','composerffmpeg','distributiondownload','episode','ingest','inspection','org','users','roles','info','recordings','search','series','services','distributionstreaming','analysistext','usertracking','analysisvideosegmenter','workflow','files' ) NOT NULL PRIMARY KEY,
                  `service_url` VARCHAR( 255 ) NOT NULL,
                  `service_user` VARCHAR( 255 ) NOT NULL,
                  `service_password` VARCHAR( 255 ) NOT NULL
                  ) ENGINE = MYISAM;");

        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_seminar_series` (
                `seminar_id` VARCHAR( 32 ) NOT NULL ,
                `series_id` VARCHAR( 64 ) NOT NULL ,
                `visibility` ENUM(  'visible',  'invisible' )NOT NULL ,
                `schedule` TINYINT( 1 ) NOT NULL DEFAULT '0',
                PRIMARY KEY (  `seminar_id` ,  `series_id` )
                ) ENGINE = MYISAM");
        
        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_resources` (
                `resource_id` VARCHAR( 32 ) NOT NULL ,
                `capture_agent` VARCHAR( 64 ) NOT NULL ,
                PRIMARY KEY (  `resource_id` ,  `capture_agent` )
                ) ENGINE = MYISAM");

        DBManager::get()->query("INSERT INTO `resources_properties`
                (`property_id`, `name`, `description`, `type`, `options`, `system`)
                VALUES (MD5('".uniqid()."'), 'Opencast Capture Agent', '', 'bool', 'vorhanden', 0)");

        
        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_seminar_episodes` (
                `seminar_id` VARCHAR( 32 ) NOT NULL ,
                `episode_id` VARCHAR( 64 ) NOT NULL ,
                `visible` ENUM( 'true', 'false' ) NOT NULL DEFAULT 'true',
                PRIMARY KEY ( `seminar_id` , `episode_id` )
                ) ENGINE = MYISAM ;");


        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_scheduled_recordings` (
                `seminar_id` VARCHAR( 32 ) NOT NULL ,
                `series_id` VARCHAR( 64 ) NOT NULL ,
                `date_id` VARCHAR( 32 ) NOT NULL ,
                `resource_id` VARCHAR( 32 ) NOT NULL ,
                `capture_agent` VARCHAR( 64 ) NOT NULL ,
                `event_id` VARCHAR( 64 ) NOT NULL,
                `status` ENUM( 'scheduled', 'recorded' ) NOT NULL ,
                PRIMARY KEY ( `seminar_id` , `series_id` , `date_id` , `resource_id` , `capture_agent` )
                ) ENGINE = MYISAM ");



    }
    
    function down() {
        DBManager::get()->query("DROP TABLE oc_series");
        DBManager::get()->query("DROP TABLE oc_config");
        DBManager::get()->query("DROP TABLE oc_seminar_series");
    }
    
}