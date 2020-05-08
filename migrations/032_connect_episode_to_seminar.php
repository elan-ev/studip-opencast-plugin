<?php

class ConnectEpisodeToSeminar extends Migration
{
    public function description()
    {
        return 'Connects episodes to series and seminars allowing for different visibilities';
    }

    public function up()
    {
        $db = DBManager::get();

        // drop old seminar_id
        $db->exec("ALTER TABLE `oc_seminar_episodes`
            ADD COLUMN `seminar_id` VARCHAR(32) NOT NULL AFTER `episode_id`,
            ADD COLUMN `chdate` INT(11) NOT NULL DEFAULT 0 AFTER `is_retracting`");

        $db->exec("ALTER TABLE `oc_seminar_episodes`
            DROP INDEX `PRIMARY`;");

        // set seminar_id for all entries, update only the first connected course
        $update = $db->prepare("UPDATE oc_seminar_episodes
            SET seminar_id = ?
            WHERE series_id = ?");

        foreach ($db->query('SELECT oss.seminar_id, ose.series_id
            FROM oc_seminar_episodes ose
            LEFT JOIN oc_seminar_series oss USING (series_id)
            WHERE oss.seminar_id IS NOT NULL') as $entry)
        {
            $update->execute([$entry['seminar_id'], $entry['series_id']]);
        }

        // set series_id and episode_id as new primary key
        $db->exec("ALTER TABLE `oc_seminar_episodes`
            ADD PRIMARY KEY `series_id_episode_id_seminar_id` (`series_id`, `episode_id`, `seminar_id`)");


        // clean out all entries without connection to a seminar
        $db->exec("DELETE FROM oc_seminar_episodes WHERE seminar_id = ''");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
    }
}
