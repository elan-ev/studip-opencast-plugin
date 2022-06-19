<?php
class AddUploadLegalInfoConf extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_UPLOAD_INFO_TEXT_HEADING',
            'section'     => 'opencast',
            'description' => 'Infotext, der auf der Hochladeseite gezeigt wird (Überschrift)',
            'range'       => 'global',
            'type'        => 'string',
            'value'       => 'Laden Sie nur Medien hoch, an denen Sie das Nutzungsrecht besitzen!'
        ]);
        $stmt->execute([
            'name'        => 'OPENCAST_UPLOAD_INFO_TEXT_BODY',
            'section'     => 'opencast',
            'description' => 'Infotext, der auf der Hochladeseite gezeigt wird',
            'range'       => 'global',
            'type'        => 'string',
            'value'       => '- Nach §60 UrhG dürfen nur maximal 5-minütige Sequenzen aus urheberrechtlich geschützten Filmen oder Musikaufnahmen'
                             . ' bereitgestellt werden, sofern diese einen geringen Umfang des Gesamtwerkes ausmachen.'
                             . '- [§60 UrhG Zusammenfassung]https://elan-ev.de/themen_p60.php'
                             . '- Medien, bei denen Urheberrechtsverstöße vorliegen, werden ohne vorherige Ankündigung umgehend gelöscht.'
        ]);

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field IN ('OPENCAST_UPLOAD_INFO_TEXT_HEADING', 'OPENCAST_UPLOAD_INFO_TEXT_BODY')");
    }

}
