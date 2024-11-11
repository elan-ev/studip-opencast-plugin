<?php
class UpdateCoursewarePayload extends Migration
{
    public function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('UPDATE cw_blocks
            SET payload = :payload
            WHERE id = :id');

        $result = $db->query("SELECT id, payload FROM cw_blocks
            WHERE block_type = 'plugin-opencast-video'");

        while ($data = $result->fetch(PDO::FETCH_ASSOC)) {
            $payload = json_decode($data['payload'], true);

            unset($payload['url']);

            $stmt->execute([
                ':payload' => json_encode($payload),
                ':id'      => $data['id']
            ]);
        }
    }

    public function down()
    {

    }
}