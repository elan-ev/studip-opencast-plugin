<?php
class UpdateCoursewarePayload extends Migration
{
    public function up()
    {
        $db = DBManager::get();

        $link = str_replace('/', '\\/',
            PluginEngine::getLink('opencast', [], 'redirect/perform/video/')
        );

        $stmt = $db->prepare('UPDATE cw_blocks
            SET payload = :payload
            WHERE id = :id');

        $result = $db->query("SELECT id, payload FROM cw_blocks
            WHERE block_type = 'plugin-opencast-video'
                AND payload NOT LIKE '%perform%'");

        echo '<pre>';

        while ($data = $result->fetch(PDO::FETCH_ASSOC)) {
            $payload = json_decode($data['payload'], true);

            $url = $payload['url'];
            preg_match('/id=(.*)/', $url, $matches);

            $video_id = null;

            if ($matches[1]) {
                $video_id = $matches[1];
            }

            if ($video_id) {
                $payload['url'] = $link . $video_id;

                $stmt->execute([
                    ':payload' => json_encode($payload),
                    ':id'      => $data['id']
                ]);
            }
        }
    }

    public function down()
    {

    }
}