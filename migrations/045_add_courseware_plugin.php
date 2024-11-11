<?php
class AddCoursewarePlugin extends Migration
{

    function up()
    {
        DBManager::get()->query("UPDATE plugins
            SET plugintype = 'StandardPlugin,StudipModule,SystemPlugin,Courseware\\\\CoursewarePlugin'
            WHERE pluginclassname = 'OpencastV3'");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
    }

}
