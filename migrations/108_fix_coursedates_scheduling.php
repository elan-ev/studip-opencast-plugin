<?php

class FixCoursedatesScheduling extends Migration
{
    public function description()
    {
        return 'Update missing coursedate_start and coursedate_end';
    }

    public function up()
    {
        $db = DBManager::get();
        $db->exec('UPDATE oc_scheduled_recordings ocs
            JOIN termine ON (termine.termin_id = ocs.date_id)
            SET coursedate_start = termine.date, coursedate_end = termine.end_time
            WHERE coursedate_start IS NULL'
        );
    }

    public function down()
    {
    }
}