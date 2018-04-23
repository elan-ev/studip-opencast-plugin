<?
use Studip\Button,
    Studip\LinkButton;
?>
<form id="upload_fom" action="<?= PluginEngine::getLink('opencast/upload/upload_file/', array('uuid' => md5(uniqid()))) ?>" enctype="multipart/form-data" method="post">
    <input type="hidden" name="series_id" value="<?=$series_id?>">
    <label id="title" for="titleField">
        <h4><?= $_('Titel') ?></h4>
    </label>
    <input class="oc_input" type="text" maxlength="255" name="title" id="titleField" required>

    <label id="creatorLabel" for="creator">
        <h4><span><?= $_("Vortragende") ?></span></h4>
    </label>

    <input class="oc_input" type="text" maxlength="255" name="creator" id="creator" value="<?=get_fullname_from_uname($GLOBALS['auth']->auth['uname']) ?>" required>


    <label id="recordingDateLabel" class="scheduler-label" for="recordDate">
        <h4><span><?= $_('Aufnahmedatum') ?></span></h4>
    </label>
    <input class="oc_input" type="date" name="recordDate" value="<?= $this->date ?>" id="recordDate" size="10" required>

    <label id="startTimeLabel" for="startTimeHour">
        <h4><span><?= $_('Startzeit') ?></span></h4>
    </label>
    <select id="startTimeHour" name="startTimeHour">
        <?php for ($i = 0; $i <= 23; $i++): ?>
            <?php if ($i < 10) {
                $in = '0' . $i;
            } else {
                $in = (string) $i;
            } ?>
            <?php if ($in == $this->hour): ?>
        <option value="<?= $i ?>" selected="selected"><?= $in ?></option>
            <?php else: ?>
        <option value="<?= $i ?>"><?= $in ?></option>
            <?php endif; ?>
        <?php endfor; ?>
    </select>
    :
    <select id="startTimeMin" name="startTimeMin">
        <?php for ($i = 0; $i < 60; $i++): ?>
            <?php if ($i < 10) {
                $in = '0' . $i;
            } else {
                $in = (string) $i;
            } ?>
            <?php if ($in == $this->minute): ?>
                <option value="<?= $i ?>" selected="selected"><?= $in ?></option>
            <?php else: ?>
                <option value="<?= $i ?>"><?= $in ?></option>
            <?php endif; ?>
        <?php endfor; ?>
    </select>

    <div style="display:none;">
    <label id="contributorLabel" for="contributor">
        <h4><span><?= $_('Mitwirkende') ?></span></h4>
    </label>
    <br>
    <input type="text" maxlength="255" id="contributor" name="contributor" value="<?=get_fullname_from_uname($GLOBALS['auth']->auth['uname']) ?>">
    <br>
    <label id="subjectLabel" for="subject">
        <span><?= $_('Thema') ?></span>
    </label>
    <br>
    <input type="text" maxlength="255" id="subject" name="subject" value="Medienupload aus Stud.IP">
    <br>
    <label id="languageLabel" for="language">
        <span><?= $_('Sprache') ?></span>
    </label>
    <br>
    <input type="text" maxlength="255" id="language" name="language" value="<?='de'?>">
    <br>
    </div>

    <label id="descriptionLabel" for="description">
        <h4><span><?= $_('Beschreibung') ?></span></h4>
    </label>
    <textarea class="oc_input" cols="50" rows="5" id="description" name="description"></textarea>

    <h4><label for="video_upload">Datei:</label></h4>
    <div id="file_wrapper">
            <?= LinkButton::create($_('Datei auswählen'), null, array('id' => 'video-chooser', 'onClick' => "$('input[type=file]').trigger('click');return false;")); ?>
            <input name="video" type="file" id="video_upload">
    </div>
    <div id="upload_info">
    </div>
    <div id="progressbarholder">
        <div id="progressbar"><div id='progressbar-label'></div></div>
    </div>

    <input type="hidden" value="" name="total_file_size" id="total_file_size" />
    <input type="hidden" value="" name="file_name" id="file_name" />
     <br>
     <div class="oc_upload_legal_notice">
         <p>
             <?=$_("Laden sie nur Medien hoch an denen sie das Copyright besitzen!")?>
         </p>
         <p>
	  <?= $_("Nach §60 dürfen nur maximal 5 minütige Sequenzen aus Filmen oder Musikaufnahmen bereitgestellt werden, sofern diese einen geringen Umfang des Gesamtwerkes ausmachen.")?>
          <a href="https://elan-ev.de/themen_p60.php"><?= $_('§60 UrhG Zusammenfassung') ?></a>
         </p>
         <p>
             <?=$_("Medien bei denen Urheberrechtsverstöße vorliegen, werde ohne vorherige Ankündigung umgehend gelöscht.")?>
         </p>
     </div>
    <br>
    <br>
    <div class="form_submit">
        <?= Button::createAccept($_('Medien hochladen'), null, array('id' => 'btn_accept')) ?>
        <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/course/index')) ?>
    </div>
</form>
