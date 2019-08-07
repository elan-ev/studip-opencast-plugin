<?

use Studip\Button;
use Studip\LinkButton;

global $perm;

Helpbar::get()->addPlainText('', $_("Hier können Sie die entsprechenden Stud.IP Ressourcen mit den Capture Agents aus dem Opencast System verknüpfen."));
?>
<script language="JavaScript">
    OC.initAdmin();
</script>

<?= $this->render_partial('messages') ?>

<!-- New Table-->
<form action="<?= PluginEngine::getLink('opencast/admin/update_resource/') ?>" method=post>
    <fieldset class="conf-form-field">
        <legend><?= $_("Zuweisung der Capture Agents") ?> </legend>
        <p>
            Jeder Capture-Agent kann nur maximal einem Raum zugewiesen werden!
        </p>
        <table id="oc_resourcestab" class="default">
            <tr>
                <th><?= $_('Raum') ?></th>
                <th><?= $_('Capture Agent') ?></th>
                <th><?= $_('Workflow') ?></th>
                <th><?= $_('Status') ?></th>
                <th><?= $_('Aktionen') ?></th>
            </tr>
            <!--loop the ressources -->
            <? foreach ($resources as $resource) : ?>
                <tr>
                    <?= $this->render_partial("admin/_ca-selection", [
                        'resource'         => $resource
                        //'agents'           => $agents,
                        //'available_agents' => $available_agents
                    ]) ?>
                </tr>
            <? endforeach; ?>
        </table>
    </fieldset>
    <?
    if ($perm->have_perm('root')) { ?>
        <br>
        <?= $_('Standardworkflow für Uploads:'); ?>
        <select name="oc_course_uploadworkflow">
            <? foreach ($workflows as $workflow) : ?>
                <option value="<?= $workflow['id'] ?>" title="<?= $workflow['description'] ?>"
                    <?= ($current_workflow['workflow_id'] == $workflow['id']) ? 'selected' : '' ?>>
                    <?= $workflow['title'] ?>
                </option>
            <? endforeach; ?>
            <?
            if (!$current_workflow) {
                echo '<option selected>' . $_('Undefiniert') . '</option>';
            }
            ?>
        </select><br>
        <input name="override_other_workflows" type="checkbox"> <?=$_('Alle anderen Workflows überschreiben');?>
        <?
        if (!$current_workflow) {
            echo '<b style="color:red">' . $_('Es wurde noch kein Standardworkflow definiert!') . '</b>';
        }?>
        <br><br>
    <? } ?>
    <div>
        <?= Button::createAccept($_('Übernehmen'), ['title' => $_("Änderungen übernehmen")]); ?>
        <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/admin/resources/')); ?>
    </div>
</form>
