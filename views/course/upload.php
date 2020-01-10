<?

use Studip\Button;
use Studip\LinkButton;

?>
<form id="upload_fom"
    action="<?= PluginEngine::getLink('opencast/upload/upload_file/', ['uuid' => md5(uniqid())]) ?>"
    enctype="multipart/form-data"
    method="post" class="default"
>
    <input type="hidden" name="series_id" value="<?= $series_id ?>">

    <fieldset>
        <legend>Medienupload</legend>

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
                value="<?= get_fullname_from_uname($GLOBALS['user']->username) ?>" required>
       </label>


        <label>
            <span class="required">
                <?= $_('Aufnahmedatum') ?>
            </span>

            <input class="oc_input" type="date" name="recordDate"
                value="<?= date('Y-m-d') ?>" id="recordDate" size="10" required>
        </label>

        <label>
            <?= $_('Startzeit') ?>
            <input type="time" name="startTime">
        </label>

        <label>
            <?= $_('Mitwirkende') ?>
            <input type="text" maxlength="255" id="contributor" name="contributor"
                value="<?= get_fullname_from_uname($GLOBALS['auth']->auth['uname']) ?>">
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
                    <?= $_('Es wurde noch kein Standardworkflow eingestellt. Der Upload ist erst möglich nach Einstellung eines Standard- oder eines Kursspezifischen Workflows!') ?>
                </p>
            <? else : ?>
                <p><?= $workflow_text ?: $workflow['workflow_id'] ?></p>
            <? endif ?>
        </label>

        <label>
            <?= $_('Chunk-Größe für Uploads') ?>
            <p><?= round($config['upload_chunk_size'] / 1024 / 1024, 2) ?> MB</p>
        </label>
    </section>

        <label for="video_upload">
            <span class="required">
                <?= $_('Datei') ?>
            </span>

            <div id="file_wrapper">
                <?= LinkButton::create($_('Datei auswählen'), null, [
                    'id'      => 'video-chooser',
                    'onClick' => "$('input[type=file]').trigger('click');return false;"
                ]); ?>
                <input name="video" type="file" id="video_upload">
            </div>
        </label>
        <div id="upload_info">
        </div>
        <div id="progressbarholder">
            <div id="progressbar">
                <div id='progressbar-label'></div>
            </div>
        </div>

        <input type="hidden" value="" name="total_file_size" id="total_file_size"/>
        <input type="hidden" value="" name="file_name" id="file_name"/>

        <hr>

        <div style="max-width: 48em">
            <p>
                <?= $_("Laden Sie nur Medien hoch, an denen Sie das Copyright besitzen!") ?>
            </p>
            <p>
                <?= $_("Nach §60 UrhG dürfen nur maximal 5-minütige Sequenzen aus urheberrechtlich geschützten Filmen oder Musikaufnahmen bereitgestellt werden, sofern diese einen geringen Umfang des Gesamtwerkes ausmachen.") ?>
                <a href="https://elan-ev.de/themen_p60.php"><?= $_('§60 UrhG Zusammenfassung') ?></a>
            </p>
            <p>
                <?= $_("Medien, bei denen Urheberrechtsverstöße vorliegen, werden ohne vorherige Ankündigung umgehend gelöscht.") ?>
            </p>
        </div>
    </fieldset>

    <footer data-dialog-button>
        <? if ($workflow): ?>
            <?= Button::createAccept($_('Medien hochladen'), null, ['id' => 'btn_accept']) ?>
        <? endif ?>

        <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/course/index')) ?>
    </footer>
</form>

<script type="text/javascript">
jQuery(function() {
    OC.initUpload(<?= $config['upload_chunk_size'] ?>);
});
</script>
