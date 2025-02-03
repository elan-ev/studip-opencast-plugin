<?php
class AlterLinkshareTable extends Migration
{
    public function description()
    {
        return 'Alter table for link sharing functionality';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video_shares`
            CHANGE `password` `uuid` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NULL,
            MODIFY `token` varchar(16) NOT NULL,
            DROP PRIMARY KEY,
            DROP COLUMN `id`,
            ADD PRIMARY KEY `video_id_token` (`video_id`, `token`),
            ADD FOREIGN KEY (`video_id`) REFERENCES `oc_video` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video_shares`
            CHANGE `uuid` `password` varchar(255) NULL,
            MODIFY `token` varchar(32) NOT NULL,
            DROP PRIMARY KEY,
            ADD COLUMN `id` int(11) NOT NULL AUTO_INCREMENT FIRST,
            ADD PRIMARY KEY `id` (`id`),
            ADD FOREIGN KEY (`video_id`) REFERENCES `oc_video` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ");

        SimpleOrMap::expireTableScheme();
    }
}