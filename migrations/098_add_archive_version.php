<?php

class AddArchiveVersion extends Migration
{
    public function description()
    {
        return 'Add Opencast archive version to Stud.IP for finding videos worth to reinspect';
    }

    public function up()
    {
        $db = DBManager::get();

        $stmt = $db->exec('ALTER TABLE oc_video
            ADD COLUMN `version` INT NOT NULL DEFAULT 0 AFTER config_id');
    }

    public function down()
    {

    }
}