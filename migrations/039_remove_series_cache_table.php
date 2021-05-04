<?php

class RemoveSeriesCacheTable extends Migration {

    function up()
    {
        DBManager::get()->query("DROP TABLE IF EXISTS `oc_series_cache`;");
    }

    function down()
    {
        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_series_cache`  (
              `series_id` varchar(255) NOT NULL,
              `content` longtext NOT NULL,
              `mkdate` INT DEFAULT 0,
              `chdate` INT DEFAULT 0,
              PRIMARY KEY (`series_id`)
              ) ROW_FORMAT=DYNAMIC;");
    }
}
