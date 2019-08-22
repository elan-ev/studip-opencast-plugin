<?php

class UpdateOcConfig extends Migration
{
    public function description()
    {
        return 'sets missing primary key for oc_config';
    }

    public function up()
    {
        DBManager::get()->exec("ALTER TABLE `oc_config_precise`
            ADD PRIMARY KEY `oc_config_precise_id_uindex` (`id`),
            DROP INDEX `oc_config_precise_id_uindex`");

        DBManager::get()->exec("ALTER TABLE `oc_config`
            ADD PRIMARY KEY `config_id` (`config_id`),
            DROP INDEX `config_id`");

        SimpleORMap::expireTableScheme();
    }

    function down() {}
}
