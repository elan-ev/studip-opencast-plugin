<?php
class AddAlternateScheduleTimes extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $db->query("ALTER TABLE `oc_scheduled_recordings`
            ADD COLUMN `start` INT NOT NULL AFTER `resource_id`,
            ADD COLUMN `end` INT NOT NULL AFTER `start`");

        $db->query("ALTER TABLE `oc_scheduled_recordings`
            ADD PRIMARY KEY `event_id` (`event_id`),
            DROP INDEX `PRIMARY`");

        $results = $db->query("SELECT osr.event_id, termine.date, termine.end_time
            FROM oc_scheduled_recordings AS osr
            LEFT JOIN termine ON (termine.termin_id = osr.date_id)");

        $stmt = $db->prepare('UPDATE oc_scheduled_recordings
            SET start = ?, end = ?
            WHERE event_id = ?');

        while ($data = $results->fetch(PDO::FETCH_ASSOC)) {
            $stmt->execute([
                $data['date'],
                $data['end_time'],
                $data['event_id']
            ]);
        }

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
    }

}
