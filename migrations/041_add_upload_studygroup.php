<?php
class AddUploadStudygroup extends Migration
{

    function up()
    {
        DBManager::get()->query('CREATE TABLE IF NOT EXISTS oc_upload_studygroup (
                course_id VARCHAR(32) NOT NULL,
                studygroup_id VARCHAR(32) NOT NULL,
                active BOOLEAN NOT NULL DEFAULT TRUE,
                PRIMARY KEY ( course_id )
                );'
        );

        $stmt = DBManager::get()->prepare('INSERT IGNORE INTO oc_upload_studygroup (course_id, studygroup_id, active)
                                VALUES (:course_id, :studygroup_id, :active)');

        foreach (DBManager::get()->query('SELECT range_id, value FROM config_values WHERE field = "OPENCAST_MEDIAUPLOAD_STUDY_GROUP" AND value IS NOT NULL;') as $link)
        {
            $stmt->execute(['course_id' => $link['range_id'],
                            'studygroup_id' => $link['value'],
                            'active' => TRUE]);
        }

        DBManager::get()->query('DELETE FROM config_values WHERE field = "OPENCAST_MEDIAUPLOAD_LINKED_COURSE";');
        DBManager::get()->query('DELETE FROM config_values WHERE field = "OPENCAST_MEDIAUPLOAD_STUDY_GROUP";');
    }

    function down()
    {

    }

}
