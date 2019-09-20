<?php

class ChangeSeminarEpisodes extends Migration
{
    public function description()
    {
        return 'Connects episodes to series and not to seminars';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->query("INSERT IGNORE INTO `oc_config_precise`
            (`id`, `name`, `description`, `value`, `for_config`) VALUES
            (11, 'paella', 'Soll der Paella Player verwendet werden statt Theodul?', '0', -1)
        ");

        // Find all invisible episodes and make sure they stay invisible
        $inv_episodes = $db->query("SELECT DISTINCT episode_id FROM oc_seminar_episodes
            WHERE visible = 'invisible'")->fetchAll(PDO::FETCH_COLUMN);

        // add series_id as table-field
        $db->exec("ALTER TABLE `oc_seminar_episodes`
            ADD COLUMN `series_id` varchar(64) NOT NULL AFTER `seminar_id`");

        // set series_id for all entries
        $update = $db->prepare('UPDATE oc_seminar_episodes
            SET series_id = ? WHERE seminar_id = ?');

        foreach ($db->query('SELECT ose.seminar_id, oss.series_id
            FROM oc_seminar_episodes ose
            LEFT JOIN oc_seminar_series oss USING (seminar_id)') as $entry)
        {
            $update->execute([$entry['series_id'], $entry['seminar_id']]);
        }

        // remove duplicate series_id - episode_id entries
        $db->exec("DELETE t1 FROM oc_seminar_episodes t1
            INNER JOIN oc_seminar_episodes t2
            WHERE t1.series_id = t2.series_id
                AND t1.episode_id = t2.episode_id
                AND t1.seminar_id > t2.seminar_id");

        // set series_id and episode_id as new primary key
        $db->exec("ALTER TABLE `oc_seminar_episodes`
            ADD PRIMARY KEY `series_id_episode_id` (`series_id`, `episode_id`),
            DROP INDEX `PRIMARY`;");

        // drop old seminar_id
        $db->exec("ALTER TABLE `oc_seminar_episodes`
            DROP COLUMN `seminar_id`");

        // reset invisibility of episodes
        $db->query("UPDATE oc_seminar_episodes
            SET visible = 'invisible'
            WHERE episode_id IN ('". implode("','", $inv_episodes) ."')");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        // undo table changes, loosing all entrie
        /*
        $db = DBManager::get();

        $db->exec('TRUNCATE oc_seminar_episodes');

        $db->exec("ALTER TABLE `oc_seminar_episodes`
            ADD COLUMN `seminar_id` varchar(32) NOT NULL FIRST");

        $db->exec("ALTER TABLE `oc_seminar_episodes`
            ADD PRIMARY KEY `seminar_id_episode_id` (`seminar_id`, `episode_id`),
            DROP INDEX `PRIMARY`;");

        $db->exec("ALTER TABLE `oc_seminar_episodes`
            DROP COLUMN `series_id`");
        */
    }
}
