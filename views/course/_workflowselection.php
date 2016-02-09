<?
use Studip\Button,
    Studip\LinkButton;
?>

<form  action="<?= PluginEngine::getLink('opencast/course/setworkflow/') ?>">
    <input type="hidden" name="ticket" value="<?=get_ticket()?>">
    <span class="oc_config_infotext">
        Sie können hier die Workflows für Ihren Kurs verwalten.
    </span>

    <p>
        <h4>Workflow für Aufzeichnungen</h4>
        <select name="oc_course_workflow">
            <? foreach($workflows as $workflow) :?>
                <option value="<?=$workflow['id']?>" title="<?=$workflow['description']?>" <?=($schedulewf['workflow_id'] == $workflow['id']) ? 'selected' : ''?>><?=$workflow['id']?></option>
            <? endforeach; ?>
        </select>
    </p>

    <p>
        <h4>Workflow für Uploads</h4>
        <select name="oc_course_uploadworkflow">
            <? foreach($workflows as $workflow) :?>
                <option value="<?=$workflow['id']?>" title="<?=$workflow['description']?>" <?=($uploadwf['workflow_id'] == $workflow['id']) ? 'selected' : ''?>><?=$workflow['id']?></option>
            <? endforeach; ?>
        </select>
    </p>

    <div class="form_submit">
        <?= Button::createAccept(_('Workflow zuweisen'), null, array('id' => 'btn_accept')) ?>
        <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/course/index')) ?>
    </div>
</form>