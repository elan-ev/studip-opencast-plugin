<? use Studip\Button, Studip\LinkButton; ?>
<p><?=_("Neue Series anlegen")?></p>
<div>
    <form action="<?= PluginEngine::getLink('opencast/course/create_series/') ?>"
            method=post>
        <span class="oc_config_infotext">
            <?=_('Sie können eine neue Serie für diesen Kurs in Opencast Matterhorn anlegen. Es werden 
            alle relevanten Veranstaltungsmetadaten automatisch aus Stud.IP übertragen.')?>
        </span>
        <div style="padding-top:2em;clear:both" class="form_submit">
            <?= Button::createAccept(_('Series anlegen'), array('title' => _("Series anlegen"))); ?>
            <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/course/index')); ?>
        </div>
        
    </form>
</div>

