<?php

class InitOcplugin extends Migration {
    function up() {
        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_series` (
                  `series_id` VARCHAR( 64 ) NOT NULL ,
                   `seminar_id` VARCHAR( 32 ) NULL ,
                   PRIMARY KEY ( `series_id` )
                   ) ENGINE = MYISAM;");
         DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_config` (
                  `config_id` INT NOT NULL PRIMARY KEY ,
                  `series_url` VARCHAR( 255 ) NOT NULL,
                  `search_url` VARCHAR( 255 ) NOT NULL,
                  `user` VARCHAR( 255 ) NOT NULL,
                  `password` VARCHAR( 255 ) NOT NULL
                  ) ENGINE = MYISAM;");
    }
    
    function down() {
        DBManager::get()->query("DROP TABLE oc_series");
        DBManager::get()->query("DROP TABLE oc_config");
    }
    
}