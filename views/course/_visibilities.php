<h4><?=_('Aufzeichnungen verwalten')?> </h4>
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
                    <?= $episode->mediapackage->title ?>
                </td>
                <td>
                    <? $visible = OCModel::getVisibilityForEpisode($course_id, $episode->mediapackage->id); ?>

                    <? if ($visible && $visible['visible'] == 'false') : ?>
            
                        <?= _("Unsichtbar") ?>
                    <? else : ?>
                        <?= _("Sichtbar") ?>
                    <? endif; ?>
                </td>

                <td>
                    <a href="<?= PluginEngine::getLink('opencast/course/toggle_visibility/' . $episode->mediapackage->id) ?>">
                        <?= Assets::img('icons/16/blue/visibility-visible.png', array('title' => _("Aufzeichnung unsichtbar schalten"))) ?>
                    </a>
                </td>
            </tr>
        <? endif; ?>
    <? endforeach; ?>

</table>