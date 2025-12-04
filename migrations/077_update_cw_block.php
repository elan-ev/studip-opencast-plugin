<?php
class UpdateCwBlock extends Migration
{
    public function up()
    {
        $db = DBManager::get();

        $results = $db->query("SELECT DISTINCT video_id, oc_video.token
            FROM oc_video_cw_blocks
            LEFT JOIN oc_video ON (oc_video.id = oc_video_cw_blocks.video_id)
        ");
        $entries = $results->fetchAll(PDO::FETCH_ASSOC);

        $db->exec('START TRANSACTION');

        $db->exec('ALTER TABLE oc_video_cw_blocks
            ADD `token` varchar(12) CHARACTER SET latin1 COLLATE latin1_bin AFTER video_id');


        $stmt = $db->prepare('UPDATE oc_video_cw_blocks
            SET token = :token
            WHERE video_id = :video_id
        ');

        foreach ($entries as $data) {
            // update mapping table
            $stmt->execute([
                ':token' => $data['token'],
                ':video_id' => $data['video_id']
            ]);

            // update block payloads
            $db->exec($q = "UPDATE cw_blocks
                SET payload = REPLACE(payload, '\"video_id\":\"". $data['video_id'] ."\"', '\"token\":\"". $data['token'] ."\"')
                WHERE payload LIKE '%\"video_id\":\"". $data['video_id'] ."\"%'
                    AND block_type = 'plugin-opencast-video'
            ");
        }

        $fk_name = $db->fetchColumn("SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'oc_video_cw_blocks'
              AND COLUMN_NAME = 'video_id'
              AND REFERENCED_TABLE_NAME = 'oc_video'
        ");

        if ($fk_name) {
            $db->exec("ALTER TABLE oc_video_cw_blocks DROP FOREIGN KEY `{$fk_name}`");
        }

        $db->exec('ALTER TABLE oc_video_cw_blocks
            ADD PRIMARY KEY `token_block_id` (`token`, `block_id`),
            DROP INDEX `PRIMARY`
        ');

        $db->exec('ALTER TABLE oc_video_cw_blocks
           ADD CONSTRAINT `token` FOREIGN KEY (`token`) REFERENCES `oc_video` (`token`) ON DELETE CASCADE ON UPDATE RESTRICT
        ');

        $db->exec('ALTER TABLE oc_video_cw_blocks
            DROP video_id
        ');

        $db->exec('COMMIT');
    }

    public function down()
    {
        $db = DBManager::get();
        $db->exec('START TRANSACTION');

        $db->exec('ALTER TABLE oc_video_cw_blocks
            ADD `video_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin AFTER `token`');

        $results = $db->query("SELECT DISTINCT oc_video_cw_blocks.token, oc_video.id
            FROM oc_video_cw_blocks
            LEFT JOIN oc_video ON (oc_video.token = oc_video_cw_blocks.token)
        ");
        $entries = $results->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('UPDATE oc_video_cw_blocks
            SET video_id = :video_id
            WHERE token = :token
        ');

        foreach ($entries as $data) {
            // update mapping table
            $stmt->execute([
                ':video_id' => $data['id'],
                ':token' => $data['token']
            ]);

            // update block payloads
            $db->exec("UPDATE cw_blocks
                SET payload = REPLACE(payload, '\"token\":\"". $data['token'] ."\"', '\"video_id\":\"". $data['id'] ."\"')
                WHERE payload LIKE '%\"token\":\"". $data['token'] ."\"%'
                    AND block_type = 'plugin-opencast-video'
            ");
        }

        $db->exec('ALTER TABLE oc_video_cw_blocks
            DROP FOREIGN KEY `token`
        ');

        $db->exec('ALTER TABLE oc_video_cw_blocks
            ADD PRIMARY KEY `block_id` (`block_id`),
            DROP INDEX `token_block_id`
        ');

        $db->exec('ALTER TABLE oc_video_cw_blocks
           ADD FOREIGN KEY (`video_id`) REFERENCES `oc_video` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
        ');

        $db->exec('ALTER TABLE oc_video_cw_blocks
            DROP token
        ');

        $db->exec('COMMIT');
    }
}