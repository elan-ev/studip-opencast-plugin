<?php

use Opencast\Models\Workflow;

class FixVisibilityForCourseVideos extends Migration
{
    public function description()
    {
        return 'Fix visibility for videos in course playlists';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_playlist_seminar_video`
            CHANGE `visible_timestamp` `visible_timestamp` timestamp NULL
        ");

        $db->exec("UPDATE `oc_playlist_seminar_video`
            SET `visible_timestamp` = NULL
            WHERE `visible_timestamp` = '000-00-00 00:00:00'
        ");
    }

    public function down()
    {

    }
}
