<?
use Studip\Button,
    Studip\LinkButton;
?>
<form id="upload_fom" action="<?= PluginEngine::getLink('opencast/upload/upload_file/') ?>" enctype="multipart/form-data" method="post">
    <label id="title" for="titleField">
        <?= _('Titel') ?>
    </label>
    <br>
    <input type="text" maxlength="255" name="title" id="titleField" required>
    <br>
    <label id="creatorLabel" for="creator">
        <span><?= _("Vortragende") ?></span>
    </label>
    <br>
    <input type="text" maxlength="255" name="creator" id="creator" value="<?=get_fullname_from_uname($GLOBALS['auth']->auth['uname']) ?>" required>
    <br>
    <label id="recordingDateLabel" class="scheduler-label" for="recordDate">
        <span><?= _('Aufnahmedatum') ?></span>
    </label>
    <br>
    <input type="text" name="recordDate" value="<?= $this->date ?>" id="recordDate" size="10" required>
    <br>
    <label id="startTimeLabel" for="startTimeHour">
        <span><?= _('Startzeit') ?></span>
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
    <br>
    <div style="display:none;">
    <label id="contributorLabel" for="contributor">
        <span><?= _('Mitwirkende') ?></span>
    </label>
    <br>
    <input type="text" maxlength="255" id="contributor" name="contributor" value="<?=get_fullname_from_uname($GLOBALS['auth']->auth['uname']) ?>">
    <br>
    <label id="subjectLabel" for="subject">
        <span><?= _('Thema') ?></span>
    </label>
    <br>
    <input type="text" maxlength="255" id="subject" name="subject" value="Medienupload aus Stud.IP">
    <br>
    <label id="languageLabel" for="language">
        <span><?= _('Sprache') ?></span>
    </label>
    <br>
    <input type="text" maxlength="255" id="language" name="language" value="<?='de'?>">
    <br>
    </div>
    <label id="descriptionLabel" for="description">
        <span><?= _('Beschreibung') ?></span>
    </label>
    <br>
    <textarea cols="50" rows="5" id="description" name="description"></textarea>
    <br>
    <label for="video_upload">Datei:</label>
    <br>
    <div id="file_wrapper">
            <?= LinkButton::create(_('Datei auswählen'), null, array('id' => 'video-chooser', 'onClick' => "$('input[type=file]').trigger('click');return false;")); ?>
            <input name="video" type="file" id="video_upload" required>
    </div>
    <div id="upload_info">
    </div>
    <div id="progressbarholder">
        <div id="progressbar"><div id='progressbar-label'></div></div>
    </div>
    <input type="hidden" value="" name="total_file_size" id="total_file_size" />
    <input type="hidden" value="" name="file_name" id="file_name" />
    <div class="form_submit">
        <?= Button::createAccept(_('Medien hochladen'), null, array('id' => 'btn_accept')) ?>
        <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/course/index')) ?>
    </div>
</form>