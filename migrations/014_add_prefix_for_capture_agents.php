<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (14:00)
 */

class AddPrefixForCaptureAgents extends Migration
{

    function up()
    {
        $stmt = DBManager::get()->query("UPDATE `resources_properties` SET `name`= CONCAT('OCCA#', `name`) WHERE `name`='Opencast Capture Agent'");
    }

    function down()
    {
        $stmt = DBManager::get()->query("UPDATE `resources_properties` SET `name`= 'Opencast Capture Agent' WHERE `name`='OCCA#%'");
    }

}
