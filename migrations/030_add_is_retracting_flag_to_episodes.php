<?php
/**
 * @author      <lunzenauer@elan-ev.de
 * @copyright   (c) Authors
 */
class AddIsRetractingFlagToEpisodes extends \Migration
{
    function up()
    {
        \DBManager::get()->query(
            "ALTER TABLE `oc_seminar_episodes` ADD `is_retracting` BOOLEAN NOT NULL DEFAULT FALSE AFTER `visible`;"
        );

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        \DBManager::get()->query(
            "ALTER TABLE `oc_seminar_episodes` DROP COLUMN `is_retracting`;"
        );

        SimpleOrMap::expireTableScheme();
    }
}
