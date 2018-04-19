<? use Studip\Button, Studip\LinkButton; ?>
<?
    Helpbar::get()->addPlainText('',$_("Hier können Sie die entsprechenden Stud.IP Ressourcen mit den Capture Agents aus dem Opencast System verknüpfen."));
?>
<script language="JavaScript">
OC.initAdmin();
</script>

<?= $this->render_partial('messages') ?>

<!-- New Table-->
<form action="<?= PluginEngine::getLink('opencast/admin/update_resource/') ?>" method=post>
    <fieldset class="conf-form-field">
        <legend><?= $_("Zuweisung der Capture Agents") ?> </legend>
        <table id="oc_resourcestab" class="default">
            <tr>
                <th><?=$_('Raum')?></th>
                <th><?=$_('Capture Agent')?></th>
                <th><?=$_('Workflow')?></th>
                <th><?=$_('Status')?></th>
                <th><?=$_('Aktionen')?></th>
            </tr>
            <!--loop the ressources -->
            <? foreach ($resources as $resource) :?>
                <tr>
                    <?= $this->render_partial("admin/_ca-selection", array('resource' => $resource, 'agents' => $agents, 'available_agents' => $available_agents)) ?>
                </tr>
            <? endforeach; ?>
        </table>

        <div>
            <?= Button::createAccept($_('Übernehmen'), array('title' => $_("Änderungen übernehmen"))); ?>
            <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/admin/resources/')); ?>
        </div>
    </fieldset>
</form>
