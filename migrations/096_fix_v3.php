<?php

class FixV3 extends Migration
{
    public function description()
    {
        return 'Fix cronjobs and tool activations when migrating from v2 or old v3';
    }

    public function up()
    {
        $db = DBManager::get();

        // fix cronjobs
        $tasks = $db->query("SELECT task_id, filename FROM cronjobs_tasks WHERE filename LIKE '%OpenCast%'")->fetchAll();
        $stmt  = $db->prepare("UPDATE cronjobs_tasks SET filename = :filename WHERE task_id = :task_id");

        foreach ($tasks as $task) {
            $stmt->execute([
                ':task_id'  => $task['task_id'],
                ':filename' => str_replace('/OpenCast/', '/OpencastV3/', $task['filename'])
            ]);
        }

        // fix tool activations and plugin activations
        $old_plugin_id = $db->query("SELECT pluginid FROM plugins WHERE pluginclassname = 'OpenCast'")->fetchColumn();
        $new_plugin_id = $db->query("SELECT pluginid FROM plugins WHERE pluginclassname = 'OpencastV3'")->fetchColumn();

        if (!empty($old_plugin_id) && !empty($new_plugin_id)) {
            $stmt = $db->prepare("UPDATE tools_activated SET plugin_id = ':new' WHERE plugin_id = ':old'");
            $stmt->execute([
                ':new' => $new_plugin_id,
                ':old' => $old_plugin_id
            ]);

            $stmt = $db->prepare("UPDATE plugins_activated SET pluginid = ':new' WHERE pluginid = ':old'");
            $stmt->execute([
                ':new' => $new_plugin_id,
                ':old' => $old_plugin_id
            ]);
        }

        // clear cache for autoloader classes
        $cache     = StudipCacheFactory::getCache();
        $cache_key = 'STUDIP#autoloader-classes';
        $cache->expire($cache_key);
    }

    public function down()
    {

    }
}