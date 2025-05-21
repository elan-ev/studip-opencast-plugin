<?php

class FixOrphanedPreviews extends Migration
{
    public function description()
    {
        return 'Fix for previews that could not get migrated in 107.';
    }

    public function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('UPDATE oc_video SET preview = :preview WHERE id = :id');
        $result = $db->query('SELECT id, preview FROM oc_video WHERE preview IS NOT NULL');


        while ($data = $result->fetch()) {
            $new_preview_url = null;
            $previews = json_decode($data['preview'], true);

            // We want to handle the case where the preview is still a json object.
            if (!empty($previews)) {
                // Last chance to present the preview URL.
                if (!empty($previews['player'])) {
                    $new_preview_url = $previews['player'];
                } else if (!empty($previews['search'])) {
                    $new_preview_url = $previews['search'];
                }

                $stmt->execute([
                    ':preview' => $new_preview_url,
                    ':id' => $data['id']
                ]);
            }
        }
    }

    public function down()
    {
    }
}
