<?php
class ChangeViewsNonNull extends Migration
{
    public function description()
    {
        return 'change view counter type';
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