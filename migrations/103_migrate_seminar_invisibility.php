<?php
class MigrateSeminarInvisibility extends Migration
{
    public function description()
    {
        return 'Migrate opencast tool invisibility';
    }

    public function up()
    {
        $db = DBManager::get();

        $plugin_id = $db->query("SELECT pluginid FROM plugins WHERE pluginclassname = 'OpencastV3'")->fetchColumn();

        $invisible_courses = $db->query("SELECT DISTINCT seminar_id FROM `oc_seminar_series` 
            WHERE visibility = 'invisible'")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($invisible_courses as $course_id) {
            $opencast_tool = ToolActivation::find([$course_id, $plugin_id]);

            if (!empty($opencast_tool)) {
                $opencast_tool->metadata['visibility'] = 'tutor';
                $opencast_tool->store();
            }
        }

        $db->exec("ALTER TABLE `oc_seminar_series` DROP COLUMN `visibility`");
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_seminar_series`
            ADD COLUMN `visibility` ENUM('visible', 'invisible') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT 'visible'
        ");

        $plugin_id = $db->query("SELECT pluginid FROM plugins WHERE pluginclassname = 'OpencastV3'")->fetchColumn();

        $opencast_tools = ToolActivation::findBySQL("plugin_id = ? AND range_type = 'course'", [$plugin_id]);

        foreach ($opencast_tools as $tool) {
            $visibility = $tool->metadata['visibility'] == 'tutor' ? 'invisible' : 'visible';

            $stmt = $db->prepare("UPDATE oc_seminar_series SET visibility = :visibility WHERE seminar_id = :seminar_id");
            $stmt->execute([
                ':visibility' => $visibility,
                ':seminar_id' => $tool->range_id,
            ]);
        }
    }
}