<?

use Studip\Button;
use Studip\LinkButton;

?>
<form id="upload_form" action="#" enctype="multipart/form-data" method="post" class="default">

    <input type="hidden" name="series_id" value="<?= $series_id ?>">

    <label>
        <span class="required">
            <?= $_('Titel') ?>
        </span>

        <input class="oc_input" type="text" maxlength="255" name="title" id="titleField" required>
    </label>


    <label>
        <span class="required">
            <?= $_("Vortragende") ?>
        </span>

        <input class="oc_input" type="text" maxlength="255" name="creator" id="creator"
               value="<?= get_fullname_from_uname(
                      $GLOBALS['user']->username
                      ) ?>" required>
    </label>


    <label>
        <span class="required">
            <?= $_('Aufnahmezeitpunkt') ?>
        </span>

        <input class="oc_input" type="text" name="recordDate" value="<?= date(
                                                                     'd.m.Y H:i'
                                                                     ) ?>" id="recordDate" maxlength="10" required data-datetime-picker>
    </label>

    <label>
        <?= $_('Mitwirkende') ?>
        <input type="text" maxlength="255" id="contributor" name="contributor"
               value="<?= get_fullname_from_uname(
                      $GLOBALS['auth']->auth['uname']
                      ) ?>">
    </label>

    <label>
        <?= $_('Thema') ?>
        <input type="text" maxlength="255" id="subject"
               name="subject" value="Medienupload, Stud.IP">
    </label>

    <label style="display:none">
        <?= $_('Sprache') ?>
        <input type="text" maxlength="255" id="language" name="language" value="<?= 'de' ?>">
    </label>

    <label>
        <?= $_('Beschreibung') ?>
        <textarea class="oc_input" cols="50" rows="5" id="description" name="description"></textarea>
    </label>

    <section class="hgroup">
        <label>
            <?= $_('Eingestellter Workflow') ?>

            <? if (!$workflow) : ?>
                <p style="color:red; max-width: 48em;">
                    <?= $_(
                        'Es wurde noch kein Standardworkflow eingestellt. Der Upload ist erst möglich nach Einstellung eines Standard- oder eines Kursspezifischen Workflows!'
                    ) ?>
                </p>
            <? else : ?>
                <p><?= $workflow_text ?: $workflow['workflow_id'] ?></p>
            <? endif ?>
        </label>

        <label>
            <?= $_('Chunk-Größe für Uploads') ?>
            <p><?= round(
                $config['upload_chunk_size'] / 1024 / 1024,
                2
               ) ?> MB</p>
        </label>
    </section>

    <label for="video_upload">
        <span class="required">
            <?= $_('Datei(en)') ?>
        </span>
        <p class="help">
            <?= $_("Mindestens ein Video wird benötigt.") ?>
        </p>
    </label>

    <ul class="oc-media-upload-info">
    </ul>

    <div>
        <?= LinkButton::createAdd($_('Aufzeichnung des/der Vortragende*n hinzufügen'), null, [ 'class' => 'oc-media-upload-add', 'data-flavor' => 'presenter/source']) ?>
        <input type="file" class="video_upload" data-flavor="presenter/source" accept=".avi,.mkv,.mp4,.webm,.mov,.ogg,.ogv,video/mp4,video/x-m4v,video/webm,video/ogg,video/mpeg,video/*">

        <?= LinkButton::createAdd($_('Aufzeichnung der Folien hinzufügen'), null, [ 'class' => 'oc-media-upload-add', 'data-flavor' => 'presentation/source']) ?>
        <input type="file" class="video_upload" data-flavor="presentation/source" accept=".avi,.mkv,.mp4,.webm,.mov,.ogg,.ogv,video/mp4,video/x-m4v,video/webm,video/ogg,video/mpeg,video/*">
    </div>

    <?= MessageBox::info(
        $_("Laden Sie nur Medien hoch, an denen Sie das Copyright besitzen!"),
        [
            $_(
                "Nach §60 UrhG dürfen nur maximal 5-minütige Sequenzen aus urheberrechtlich geschützten Filmen oder Musikaufnahmen bereitgestellt werden, sofern diese einen geringen Umfang des Gesamtwerkes ausmachen."
            ),
            '<a href="https://elan-ev.de/themen_p60.php">' .
            $_('§60 UrhG Zusammenfassung') .
            '</a>',
            $_(
                "Medien, bei denen Urheberrechtsverstöße vorliegen, werden ohne vorherige Ankündigung umgehend gelöscht."
            )
        ]
    ) ?>

    <footer>
        <? if ($workflow): ?>
            <?= Button::createAccept($_('Medien hochladen'), null, [
                'id' => 'btn_accept'
            ]) ?>
        <? endif ?>

        <?= LinkButton::createCancel(
            $_('Abbrechen'),
            PluginEngine::getLink('opencast/course/index')
        ) ?>
    </footer>
</form>

<div id="oc-media-upload-dialog" style="display: none;">
    <div class="oc-media-upload-dialog-content">
        <h1 class="hide-in-dialog"><?= $_("Medien-Upload") ?></h1>
        <p><?= $_("Ihre Medien werden gerade hochgeladen.") ?></p>
        <div>
            <ul class="files">
            </ul>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(function() {
    OC.initUpload(<?= json_encode($config['service_url']) ?>);
});
</script>
