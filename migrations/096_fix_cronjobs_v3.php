<?php

class FixCronjobsV3 extends Migration
{
    public function description()
    {
        return 'Fix cronjobs when migrating from v2 or old v3';
    }

    public function up()
    {
        $db = DBManager::get();

        $tasks = $db->query("SELECT task_id, filename FROM cronjobs_tasks WHERE filename LIKE '%OpenCast%'")->fetchAll();
        $stmt  = $db->prepare("UPDATE cronjobs_tasks SET filename = :filename WHERE task_id = :task_id");

        foreach ($tasks as $task) {
            $stmt->execute([
                ':task_id'  => $task['task_id'],
                ':filename' => str_replace('/OpenCast/', '/OpencastV3/', $task['filename'])
            ]);
        }
    }

    public function down()
    {

    }
}