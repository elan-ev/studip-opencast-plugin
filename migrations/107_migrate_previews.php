<?php

class MigratePreviews extends Migration
{
    public function description()
    {
        return 'Update preview URL in oc_video table';
    }

    public function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('UPDATE oc_video SET preview = :preview WHERE id = :id');
        $result = $db->query('SELECT id, preview FROM oc_video WHERE preview IS NOT NULL');


        while ($data = $result->fetch()) {
            $new_preview_url = null;
            $previews = json_decode($data['preview'], true);

            if (!empty($previews)) {
                if (!empty($previews['player'])) {
                    $new_preview_url = $previews['player'];
                } else if (!empty($previews['search'])) {
                    $new_preview_url = $previews['search'];
                }
            }

            // Since we are migrating previews to preview URL, we update the preview regardless of the value of the new preview url, whether it is empty or not!
            $stmt->execute([
                ':preview' => $new_preview_url,
                ':id' => $data['id']
            ]);
        }
    }

    public function down()
    {
    }
}
