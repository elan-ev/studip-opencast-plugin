<?php
class UpdateCoursewarePayload extends Migration
{
    public function up()
    {
        $db = DBManager::get();

        $link = str_replace('/', '\\/',
            PluginEngine::getLink('opencast', [], 'redirect/perform/video/')
        );

        $stmt = $db->exec($query = "UPDATE cw_blocks
            SET payload = REGEXP_REPLACE(payload, '\"url\":\".*\\\\/(.*?)\",\"', '\"url\":\"" .
                $link
            . "\\\\1\",\"')
            WHERE block_type = 'plugin-opencast-video'
        ");
    }

    public function down()
    {

    }
}