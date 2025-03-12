<?php
class MigrateMissingCwBlocks extends Migration
{
    public function description()
    {
        return 'Fix payload of cw blocks and migrate blocks to oc_video_cw_blocks';
    }

    public function up()
    {
        $db = DBManager::get();

        // add tokens to all videos, otherwise this migration will not work
        $db->exec('UPDATE oc_video SET token = LOWER(HEX(RANDOM_BYTES(6)))
            WHERE token IS NULL');

        // Database statements
        $update_payload_stmt = $db->prepare('UPDATE cw_blocks
            SET payload = :payload
            WHERE id = :id');

        $token_stmt = $db->prepare('SELECT token FROM oc_video WHERE episode = ?');

        $range_stmt = $db->prepare('SELECT DISTINCT range_id, range_type
            FROM cw_structural_elements
            INNER JOIN cw_containers ON (cw_containers.structural_element_id = cw_structural_elements.id)
            INNER JOIN cw_blocks ON (cw_blocks.container_id = cw_containers.id AND cw_blocks.id = ?)
        ');

        $insert_block_stmt = $db->prepare('INSERT IGNORE INTO `oc_video_cw_blocks` (token, block_id, seminar_id)
                VALUES (:token, :block_id, :seminar_id)');

        $blocks = $db->query("SELECT id, payload FROM cw_blocks
            WHERE block_type = 'plugin-opencast-video'");

        // Iterate over all opencast blocks
        while ($block = $blocks->fetch(PDO::FETCH_ASSOC)) {
            $payload = json_decode($block['payload'], true);

            // Set token in payload if token hasn't been migrated
            if (isset($payload['episode_id']) && !isset($payload['token'])) {
                $token_stmt->execute([$payload['episode_id']]);
                $token = $token_stmt->fetchColumn();

                if ($token) {
                    $payload['token'] = $token;
                    unset($payload['episode_id']);

                    $update_payload_stmt->execute([
                        ':payload' => json_encode($payload),
                        ':id'      => $block['id']
                    ]);
                }
            }

            // Get seminar id of block
            $range_stmt->execute([$block['id']]);
            $range = $range_stmt->fetch(PDO::FETCH_ASSOC);

            if ($range['range_type'] === 'course' && isset($payload['token'])) {
                // Insert row to oc_video_cw_blocks and ignore if exists
                $insert_block_stmt->execute([
                    'token'      => $payload['token'],
                    'block_id'   => $block['id'],
                    'seminar_id' => $range['range_id'],
                ]);
            }
        }
    }

    public function down()
    {

    }
}