<?php
class AddUploadStudygroup extends Migration
{

    function up()
    {
        DBManager::get()->query('CREATE TABLE IF NOT EXISTS oc_upload_studygroup (
                course_id VARCHAR(32) NOT NULL, 
                studygroup_id VARCHAR(32) NOT NULL, 
                PRIMARY KEY ( course_id )
                );'
        );
        
        $stmt = DBManager::get()->prepare('INSERT IGNORE INTO oc_upload_studygroup (course_id, studygroup_id)
                                VALUES (:course_id, :studygroup_id)');

        foreach (DBManager::get()->query('SELECT range_id, value FROM config_values WHERE field = "OPENCAST_MEDIAUPLOAD_STUDY_GROUP" AND value IS NOT NULL;') as $link)
        {
            $stmt->execute(['course_id' => $link['range_id'], 
                            'studygroup_id' => $link['value']]);
        }

        DBManager::get()->query('DELETE FROM config_values WHERE field = "OPENCAST_MEDIAUPLOAD_LINKED_COURSE";');
        DBManager::get()->query('DELETE FROM config_values WHERE field = "OPENCAST_MEDIAUPLOAD_STUDY_GROUP";');
    }

    function down()
    {
        $stmt = DBManager::get()->prepare('INSERT IGNORE INTO config_values (field, range_id, value, mkdate, chdate, comment)
                                VALUES (:name, :range_id, :value, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), "")');
        
        foreach (DBManager::get()->query('SELECT * FROM oc_upload_studygroup;') as $link)
        {
            $stmt->execute(['name' => "OPENCAST_MEDIAUPLOAD_LINKED_COURSE",
                            'range_id' => $link['studygroup_id'], 
                            'value' => $link['course_id']]);
            $stmt->execute(['name' => "OPENCAST_MEDIAUPLOAD_STUDY_GROUP", 
                            'range_id' => $link['course_id'], 
                            'value' => $link['studygroup_id']]);
        }

        DBManager::get()->query('DROP TABLE IF EXISTS oc_upload_studygroup;');
    }

}
