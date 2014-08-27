<? use Studip\Button, Studip\LinkButton; ?>
    <p><?= _('Serie verknüpfen') ?>:</p>
    <? if (false && sizeof($connectedSeries) == 0) : ?>
        <div>
        <?= MessageBox::info(_("Es sind bislang noch keine Series verfügbar. Bitte überprüfen Sie die globalen Opencast Matterhorn Einstellungen.")) ?>
        </div>
    <? else : ?>
        <div>
            <span class="oc_config_infotext">
                <?=_('Wählen Sie eine Series aus, die Sie mit der aktuellen Veranstaltung verknüpfen möchten.')?>
            </span>
            <form action="<?= PluginEngine::getLink('opencast/course/edit/' . $course_id) ?>"
              method=post id="select-series" data-unconnected="<?= (empty($connectedSeries) ? 1 : 'false');?>">
                <div style="text-align: center;">
                    <? if (!empty($unconnectedSeries)) : ?>
                        <select class="series_select" multiple="multiple" name="series[]">

                            <? foreach ($unconnectedSeries as $serie) : ?>
                                <? if (isset($serie['identifier'])) : ?>

                                    <option value="<?= $serie['identifier'] ?>">
                                        <?= utf8_decode($serie['title'])?>
                                        <? //= $series->additionalMetadata?>
                                    </option>
                                <? endif ?>
                            <? endforeach ?>
                        </select>
                    <? endif ?>
                </div>
                <div style="padding-top:2em;clear:both" class="form_submit change_series">
                    <?= Button::createAccept(_('Übernehmen'), array('title' => _("Änderungen übernehmen"))); ?>
                    <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/course/index')); ?>
                </div>
            </form>
        </div>
    <? endif; ?>