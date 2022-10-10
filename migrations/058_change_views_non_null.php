<?php
class ChangeViewsNonNull extends Migration
{
    public function description()
    {
        return 'Add config to show or hide the scheduler functionality';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("
            ALTER TABLE oc_video
            MODIFY `views` int(11) NOT NULL DEFAULT 0;
        ");


        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        SimpleOrMap::expireTableScheme();
    }
}