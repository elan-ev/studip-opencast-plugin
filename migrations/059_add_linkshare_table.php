<?php
class AddLinkshareTable extends Migration
{
    public function description()
    {
        return 'Add table for link sharing functionality';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("
            CREATE TABLE IF NOT EXISTS `oc_video_shares` (
                `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `token` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                `video_id` int NOT NULL,
                `password` varchar(255) NULL,
                FOREIGN KEY (`video_id`) REFERENCES `oc_video` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            );
        ");


        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        SimpleOrMap::expireTableScheme();
    }
}