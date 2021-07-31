<?

use Studip\Button;
use Studip\LinkButton;


Helpbar::get()->addPlainText(
    '',
    $_('Hier können Sie die entsprechenden Stud.IP Ressourcen mit den Capture Agents aus dem Opencast System verknüpfen.')
);
?>
<script language="JavaScript">
    OC.initAdmin();
</script>

<?= $this->render_partial('messages') ?>

<!-- New Table-->
<form action="<?= $controller->url_for('admin/update_resource') ?>" method="post" class="default">
    <fieldset class="conf-form-field">
        <legend>
            <?= $_('Zuweisung der Capture Agents') ?>
        </legend>

        <?= MessageBox::info($_('Jeder Capture-Agent kann nur maximal einem Raum zugewiesen werden!')) ?>

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
                        'resource' => $resource
                        //'agents'           => $agents,
                        //'available_agents' => $available_agents
                    ]) ?>
                </tr>
            <? endforeach; ?>
        </table>
    </fieldset>

    <? if ($GLOBALS['perm']->have_perm('root')) : ?>
        <fieldset>
            <legend>
                <?= $_('Standardworkflow') ?>
            </legend>

            <label>
                <?= $_('Standardworkflow für hochgeladene Medien:'); ?>
                <select name="oc_course_uploadworkflow">
                    <? foreach ($workflows as $workflow) : ?>
                        <option value="<?= $workflow['id'] ?>" title="<?= $workflow['description'] ?>"
                            <?= ($current_workflow['workflow_id'] == $workflow['id']) ? 'selected' : '' ?>>
                            <?= htmlReady($workflow['title']) ?> (<?= htmlReady($workflow['id']) ?>)
                        </option>
                    <? endforeach; ?>
                    <?
                    if (!$current_workflow) :?>
                        <option selected><?= $_('Undefiniert') ?></option>
                    <? endif ?>
                </select>
            </label>

            <label>
                <input name="override_other_workflows" type="checkbox">
                <?= $_('Alle anderen Workflows überschreiben'); ?>
            </label>

            <? if (!$current_workflow) : ?>
                <p style="color:red">
                    <?= $_('Es wurde noch kein Standardworkflow definiert!') ?>
                </p>
            <? endif ?>
        </fieldset>
    <? endif ?>

    <footer>
        <?= Button::createAccept($_('Übernehmen'), ['title' => $_('Änderungen übernehmen')]); ?>
        <?= LinkButton::createCancel($_('Abbrechen'), $controller->url_for('admin/resources')); ?>
    </footer>
</form>
