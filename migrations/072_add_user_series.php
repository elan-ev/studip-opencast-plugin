<?php

class AddUserSeries extends Migration
{
    public function description()
    {
        return 'Add table for user series';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_user_series` (
            `config_id` INT NOT NULL DEFAULT 1,
            `user_id` VARCHAR( 32 ) NOT NULL ,
            `series_id` VARCHAR( 64 ) NOT NULL ,
            `visibility` ENUM(  'visible',  'invisible' )NOT NULL ,
            `chdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `mkdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            PRIMARY KEY (`user_id` ,`series_id` ),
            KEY `U.1` (`series_id`, `config_id`),
            FOREIGN KEY (`config_id`) REFERENCES `oc_config`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
            );");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('DROP TABLE `oc_user_series`');

        SimpleOrMap::expireTableScheme();
    }
}