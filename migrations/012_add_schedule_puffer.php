<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (14:00)
 */

class AddSchedulePuffer extends Migration
{

    function up()
    {
        DBManager::get()->query("ALTER TABLE `oc_config` ADD `schedule_time_puffer_seconds` int DEFAULT 300 NOT NULL;");
    }

    function down()
    {
        DBManager::get()->query("ALTER TABLE `oc_config` DROP COLUMN `schedule_time_puffer_seconds`;");
    }

}
