<? use Studip\Button, Studip\LinkButton; ?>
<?
    $infobox_content = array(array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(array(
            'icon' => 'icons/16/black/info.png',
            'text' => _("Hier können Sie die entsprechenden Stud.IP Ressourcen mit den Capture Agents aus dem Opencast Matterhorn System verknüpfen.")
        ))
    ));
    $infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>
<script language="JavaScript">
OC.initAdmin();
</script>

<?= $this->render_partial('messages') ?>

<!-- New Table-->

<form action="<?= PluginEngine::getLink('opencast/admin/update_resource/') ?>" method=post>
    <fieldset class="conf-form-field">
        <legend><?= _("Zuweisung der Capture Agents") ?> </legend>
        <table id="oc_resourcestab" class="default">
            <tr>
                <th><?=_('Raum')?></th>
                <th><?=_('Capture Agent')?></th>
                <th><?=_('Status')?></th>
                <th><?=_('Aktionen')?></th>
            </tr>
            <!--loop the ressources -->
            <? foreach ($resources as $resource) :?>
                <tr>
                    <?= $this->render_partial("admin/_ca-selection", array('resource' => $resource, 'agents' => $agents, 'available_agents' => $available_agents)) ?>
                </tr>    
            <? endforeach; ?>
        </table>

        <div>
            <?= Button::createAccept(_('Übernehmen'), array('title' => _("Änderungen übernehmen"))); ?>
            <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/admin/resources/')); ?>
        </div>
    </fieldset>
</form>
