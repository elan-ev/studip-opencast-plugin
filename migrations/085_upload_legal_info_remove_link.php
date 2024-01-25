<?php
class UploadLegalInfoRemoveLink extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare("UPDATE config
            SET `value` = :value, type = 'string'
            WHERE field = 'OPENCAST_UPLOAD_INFO_TEXT_BODY'
        ");

        $stmt->execute([
            ':value' => '{"de_DE":"<p>Laden Sie nur Medien hoch, an denen Sie das Nutzungsrecht besitzen!</p><ul><li>Nach §60 UrhG dürfen nur maximal 5-minütige Sequenzen aus urheberrechtlich geschützten Filmen oder Musikaufnahmen bereitgestellt werden, sofern diese einen geringen Umfang des Gesamtwerkes ausmachen.</li><li>Medien, bei denen Urheberrechtsverstöße vorliegen, werden ohne vorherige Ankündigung umgehend gelöscht.</li></ul>","en_GB":"<p>Upload</p>"}'
        ]);
    }

    function down()
    {
    }
}
