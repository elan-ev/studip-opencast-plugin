<?php

class TablesOptimizations extends Migration
{
    use DatabaseMigrationTrait;

    const INDEX_PREFIX = "indx_";

    private $table_indexes_mapping = [
        'oc_video' => [
            'name' => 'video_filtering',
            'cols' => ['trashed', 'token', 'config_id', 'created'],
        ],
        'oc_video_user_perms' => [
            'name' => 'video_user_perms_video_id',
            'cols' => ['video_id'],
        ],
        'oc_config' => [
            'name' => 'config_id_active',
            'cols' => ['id', 'active'],
        ],
        'oc_video_tags' => [
            'name' => 'tag_video',
            'cols' => ['video_id', 'tag_id'],
        ],
        // Since this table has foreign key related to the index, dropping the index in down() would throw error!
        'oc_playlist_video' => [
            'name' => 'video_playlist',
            'cols' => ['video_id', 'playlist_id'],
        ],
        // Since this table has foreign key related to the index, dropping the index in down() would throw error!
        'oc_playlist_seminar' => [
            'name' => 'seminar_playlist',
            'cols' => ['seminar_id', 'playlist_id'],
        ],
    ];

    public function description()
    {
        return 'This migration contains various optimizations and indexes related to fetching videos from database';
    }

    public function up()
    {
        $db = DBManager::get();
        foreach ($this->table_indexes_mapping as $table_name => $index_data) {
            $index_name = self::INDEX_PREFIX . $index_data['name'];
            if ($this->keyExists($table_name, $index_name)) {
                continue;
            }

            $cols = array_map(function($col) { return "`{$col}`"; }, array_unique($index_data['cols']));
            $cols_str = implode(', ', $cols);

            $query = "CREATE INDEX `$index_name` ON `$table_name` ($cols_str)";
            $db->exec($query);
        }
    }

    public function down()
    {
        $db = DBManager::get();
        $db->exec("SET foreign_key_checks = 0");
        foreach ($this->table_indexes_mapping as $table_name => $index_data) {
            $index_name = self::INDEX_PREFIX . $index_data['name'];
            if (!$this->keyExists($table_name, $index_name)) {
                continue;
            }
            $query = "ALTER TABLE `$table_name` DROP INDEX `$index_name`";
            $db->exec($query);
        }
        $db->exec("SET foreign_key_checks = 1");
    }
}
