<?php
class AddIndexOcScheduledRecordings extends Migration
{
    public function up()
    {
        $db = DBManager::get();

        // delete duplicate entries from oc_scheduled_recordings
        $db->exec('DELETE osr1 FROM oc_scheduled_recordings osr1
                   JOIN oc_scheduled_recordings osr2 USING(date_id, resource_id)
                   WHERE osr1.event_id > osr2.event_id');

        $db->exec('ALTER TABLE oc_scheduled_recordings ADD UNIQUE KEY scheduled (date_id, resource_id)');

        // fix database table collations
        $db->exec('ALTER TABLE oc_scheduled_recordings
                   MODIFY series_id varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                   MODIFY event_id varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL');

        $db->exec("ALTER TABLE oc_seminar_episodes
                   MODIFY series_id varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                   MODIFY episode_id varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                   MODIFY seminar_id varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                   MODIFY visible enum('invisible', 'visible', 'free') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT 'visible'");

        $db->exec('ALTER TABLE oc_seminar_series
                   MODIFY series_id varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL');

        $db->exec('ALTER TABLE oc_tos
                   MODIFY seminar_id varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                   MODIFY user_id varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL');

        $db->exec('ALTER TABLE oc_upload_studygroup
                   MODIFY course_id varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                   MODIFY studygroup_id varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL');
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE oc_scheduled_recordings DROP KEY scheduled');
    }
}
