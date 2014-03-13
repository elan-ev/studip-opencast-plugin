<? use Studip\Button, Studip\LinkButton; ?>
<h4><?=_('Sichtbarkeiten der Aufzeichnungen verwalten')?> </h4>
<? if(!empty($episodes)) :?>
<table class="default">
    <tr>
        <th>Titel</th>
        <th>Status</th>
        <th>Aktionen</th>
    </tr>
    <? foreach ($episodes as $episode) : ?>

        <? if (isset($episode->mediapackage)) : ?>
            <tr>
                <td>
                    <?=  mb_convert_encoding($episode->dcTitle, 'ISO-8859-1', 'UTF-8') ?>
                </td>
                <td>
                    <? $visible = OCModel::getVisibilityForEpisode($course_id, $episode->mediapackage->id); ?>

                    <? if ($visible && $visible['visible'] == 'false') : ?>
                        <?= Assets::img('icons/16/blue/visibility-invisible.png', array('title' => _("Die Aufzeichnung ist momentan unsichtbar"))) ?>
                    <? else : ?>
                        <?= Assets::img('icons/16/blue/visibility-visible.png', array('title' => _("Die Aufzeichnung ist momentan sichtbar"))) ?>
                    <? endif; ?>
                </td>

                <td>
                    <?= LinkButton::create(_('Sichtbarkeit ändern'), PluginEngine::getLink('opencast/course/toggle_visibility/' . $episode->mediapackage->id)); ?>
                    
                    
                    <a href="<?= PluginEngine::getLink('opencast/course/toggle_visibility/' . $episode->mediapackage->id) ?>">
                        <?= Assets::img('icons/16/blue/visibility-visible.png', array('title' => _("Aufzeichnung unsichtbar schalten"))) ?>
                    </a>
                </td>
            </tr>
        <? endif; ?>
    <? endforeach; ?>

</table>
<? else: ?>
    <?= MessageBox::info(_("Es sind noch keine Aufzeichnungen vorhanden.")) ?>
<? endif; ?>