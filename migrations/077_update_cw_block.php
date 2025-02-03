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

        $db->exec('ALTER TABLE oc_video_cw_blocks
            DROP FOREIGN KEY `oc_video_cw_blocks_ibfk_1`
        ');

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

    }
}