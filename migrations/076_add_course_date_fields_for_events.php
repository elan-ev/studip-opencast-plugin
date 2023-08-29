<?php
class AddCourseDateFieldsForEvents extends Migration
{
    public function up()
    {
        $db = DBManager::get();

        // only execute this migration if columns are missing
        $results = $db->query("SHOW COLUMNS FROM `oc_scheduled_recordings` LIKE 'coursedate_start'");
        if (empty($results->fetchAll())) {
            $db->exec('ALTER TABLE oc_scheduled_recordings
                ADD `coursedate_end` int(11) AFTER `end`,
                ADD `coursedate_start` int(11) AFTER `end`
            ');

            // update all entries to fill new coursedate-fields
            $db->exec('UPDATE oc_scheduled_recordings
                SET coursedate_start = start, coursedate_end = end
                WHERE 1
            ');
        }
    }

    public function down()
    {
        $db->exec('ALTER TABLE oc_scheduled_recordings
            DROP `coursedate_end` ,
            DROP `coursedate_start`
        ');
    }
}