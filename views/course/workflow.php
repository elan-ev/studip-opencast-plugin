<?
use Studip\Button,
    Studip\LinkButton;
?>

<form class="default" action="<?= PluginEngine::getLink('opencast/course/setworkflow/') ?>" method="post">
    <input type="hidden" name="ticket" value="<?=get_ticket()?>">

    <fieldset>
        <legend>
            <?= $_('Workflow-Konfiguration') ?>
        </legend>
        <?=
            '<p>'.
            $_('Bereits geplante Aufzeichnungen werden weiterhin mit dem vorherigen Workflow verarbeitet.')
            .'</br><b style="color:orangered">'.
            $_('Die Änderung hier gilt nur für neue Aufzeichnungsplanungen!')
            .'</b>'.
            '</p>';
        ?>
        <label>
            <?= $_('Workflow für Uploads'); ?>

            <select name="oc_course_uploadworkflow">
                <? foreach($workflows as $workflow) :?>
                    <option value="<?=$workflow['id']?>" title="<?=$workflow['description']?>"
                            <?=($uploadwf['workflow_id'] == $workflow['id']) ? 'selected' : ''?>>
                        <?=$workflow['title']?>
                    </option>
                <? endforeach; ?>
            </select>
        </label>
    </fieldset>

    <footer data-dialog-button>
        <?= Button::createAccept($_('Workflow zuweisen'), null, array('id' => 'btn_accept')) ?>
        <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/course/index')) ?>
    </footer>
</form>
