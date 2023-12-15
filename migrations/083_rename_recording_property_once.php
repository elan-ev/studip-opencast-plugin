<?php
class RenameRecordingPropertyOnce extends Migration
{
    public function description()
    {
        return 'Set the name of the recording property to "Aufzeichnugstechnik" if it still has the default, ugly, naming';
    }

    public function up()
    {
        $property_id = Config::get()->OPENCAST_RESOURCE_PROPERTY_ID;

        $db = DBManager::get();

        $stmt = $db->prepare("SELECT name FROM `resource_property_definitions`
            WHERE property_id = ?");
        $stmt->execute([$property_id]);

        $name = $stmt->fetchColumn();

        if ($name == 'Opencast Capture Agent' || $name == 'OCCA#Opencast Capture Agent') {
            $stmt_update = $db->prepare('UPDATE  `resource_property_definitions`
                SET name = ?
                WHERE property_id = ?');

            $stmt_update->execute(['Aufzeichnungstechnik', $property_id]);
        }
    }

    public function down()
    {
    }
}