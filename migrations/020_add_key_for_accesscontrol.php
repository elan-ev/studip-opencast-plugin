<?php

class AddKeyForAccesscontrol extends Migration
{
    public function description()
    {
        return 'Add new primary key to oc_access_control';
    }

    public function up()
    {
        $db = DBManager::get();

        try {
            $db->exec("ALTER TABLE `oc_access_control`
                DROP INDEX `PRIMARY`");
        } catch (PDOException $e) {}

        $db->exec("ALTER TABLE `oc_access_control`
            ADD PRIMARY KEY `id_course_id` (`id`(64), `course_id`(32))");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
    }
}
