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
        if (StudipVersion::newerThan('4.4'))
        {
            $stmt = DBManager::get()->query("UPDATE `resource_property_definitions`
            SET `name`= CONCAT('OCCA#', `name`)
            WHERE `name`='Opencast Capture Agent'");
        }
        else
        {
            $stmt = DBManager::get()->query("UPDATE `resources_properties`
            SET `name`= CONCAT('OCCA#', `name`)
            WHERE `name`='Opencast Capture Agent'");
        }
    }

    function down()
    {
        if (StudipVersion::newerThan('4.4'))
        {
            $stmt = DBManager::get()->query("UPDATE `resource_property_definitions`
            SET `name`= 'Opencast Capture Agent'
            WHERE `name`='OCCA#%'");
        }
        else
        {
            $stmt = DBManager::get()->query("UPDATE `resources_properties`
            SET `name`= 'Opencast Capture Agent'
            WHERE `name`='OCCA#%'");
        }
    }

}
