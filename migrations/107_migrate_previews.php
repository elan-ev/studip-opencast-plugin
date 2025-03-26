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
            $new_preview = null;
            if (json_validate($data['preview'])) {
                $previews = json_decode($data['preview'], true);

                if (!empty($previews['player'])) {
                    $new_preview = $previews['player'];
                } else if (!empty($previews['search'])) {
                    $new_preview = $previews['search'];
                }
            }

            if (!empty($new_preview)) {
                $stmt->execute([
                    ':preview' => $new_preview,
                    ':id' => $data['id']
                ]);
            }
        }
    }

    public function down()
    {
    }
}