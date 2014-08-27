<? use Studip\Button, Studip\LinkButton; ?>
<? if (empty($this->connectedSeries)) : ?>
    <?= MessageBox::info(_("Sie haben noch keine Series aus Opencast mit dieser Veranstaltung verknüpft.
                                Bitte erstellen Sie eine neue Series oder verknüpfen eine bereits vorhandene Series."))?> 
    
    <div id="admin-accordion">
        <?= $this->render_partial("course/_createSeries", array()) ?>
        <?= $this->render_partial("course/_connectedSeries", array('course_id' => $course_id, 'connectedSeries' => $connectedSeries, 'unonnectedSeries' => $unonnectedSeries, 'series_client' => $series_client)) ?>
    </div>
<? else:?>
    <h2><?= _("Zugeordnete Series") ?></h2>
    <p><?= _("Sie können hier die Verknüpfung aufheben. Klicken Sie hierfür auf das entsprechende Mülltonnensymbol") ?></p>
    <div>
        <? if (!empty($connectedSeries)) : ?>
            <? foreach ($connectedSeries as $serie) : ?>
                    <?= utf8_decode($serie['title']) ?>
                    <a href="<?= PluginEngine::getLink('opencast/course/remove_series/' . $course_id . '/' . $serie['identifier']) ?>">
                        <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Zuordnung löschen'), 'alt' => _('Zuordnung löschen'))) ?>
                    </a>
            <? endforeach ?>
        <? endif ?>
        <div style="padding-top:2em;clear:both" class="form_submit">
            <?= LinkButton::create(_('OK'), PluginEngine::getLink('opencast/course/index')); ?>
            <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/course/index')); ?>
        </div>
    </div>
<? endif; ?>






