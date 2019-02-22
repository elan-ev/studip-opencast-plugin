<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (14:00)
 */

class AccessControl extends Migration
{

    function up()
    {
        $stmt = DBManager::get()->query("CREATE TABLE `oc_access_control` ( `course_id` TEXT NOT NULL ,  `acl_visible_id` INT NOT NULL ,  `acl_invisible_id` INT NOT NULL )");
    }

    function down()
    {
        $stmt = DBManager::get()->query("DROP TABLE `oc_access_control`");
    }

}
