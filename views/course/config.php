<? if (isset($message)): ?>
  <?= MessageBox::success($message) ?>
<? endif ?>
<? if ($flash['question']) : ?>
    <?= $flash['question'] ?>
<? endif  ?>


<?
$infobox_content = array(array(
    'kategorie' => _('Hinweise:'),
    'eintrag'   => array(array(
        'icon' => 'icons/16/black/info.png',
        'text' => _("Hier können Sie die Veranstaltung mit einer Series in Opencast Matterhorn verknüpfen. Sie können entweder aus vorhandenen Series wählen oder eine Series für diese Veranstaltung anlegen.")
    ))
));
?>
<script language="JavaScript">
OC.initAdmin();
</script>

<h3><?=_('Verwaltung der eingebundenen Vorlesungsaufzeichnungen')?></h3>

<? if (empty($this->cseries)) : ?>
    <?= MessageBox::info(sprintf(_("Sie haben noch keine Series aus Opencast mit dieser Veranstaltung verknüpft.
                            Bitte verknüpfen eine bereits vorhandene Series oder %s erstellen Sie eine neue.%s"), 
                         '<a href="'.PluginEngine::getLink('opencast/course/create_series/') . '">', '</a>')) ?>
    <div id="admin-accordion">
        <h3><?=_('Wählen Sie unten eine Series aus, die Sie mit der aktuellen Veranstaltung verknüpfen möchten')?>:</h3>
        <?= $this->render_partial("course/_connectedSeries", array('course_id' => $course_id, 'cseries' => $cseries, 'rseries' => $rseries, 'series_client' => $series_client)) ?>
    </div>


<? elseif(!$connected && !empty($this->cseries)) : ?>
    <h4> <?=_('Verknüpfte Serie:')?> </h4>
    <? $x = 'http://purl.org/dc/terms/'; ?>
    <div>
        <?= $serie_name->$x->title[0]->value ?>
        <a href="<?=PluginEngine::getLink('opencast/course/remove_series/'.$serie_id.'/true' ) ?>">
            <?= Assets::img('icons/16/blue/trash.png', array('title' => _("Verknüpfung aufheben"))) ?>
        </a>
    </div>

<? elseif($connected): ?>
    <h4> <?=_('Verknüpfte Serie:')?> </h4>
    <? $x = 'http://purl.org/dc/terms/'; ?>
    <div>
        <?= $serie_name->$x->title[0]->value ?>
        <a href="<?=PluginEngine::getLink('opencast/course/remove_series/'.$serie_id.'/false' ) ?>">
            <?= Assets::img('icons/16/blue/trash.png', array('title' => _("Verknüpfung aufheben"))) ?>
        </a>
    </div>
    <? if(empty($episodes)) :?>
        <?= MessageBox::info(_("Es sind bislang keine Vorlesungsaufzeichnungen verfügbar.")) ?>
    <? else: ?>
        <h4> <?=_('Verfügbare Vorlesungsaufzeichnungen bearbeiten:')?> </h4>

        <table class="default">
                <tr>
                    <th>Titel</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
                <? foreach($episodes as $episode) :?>

                    <? if(isset($episode->mediapackage)) :?>
                    <tr>
                        <td>
                            <?=$episode->mediapackage->title ?>
                        </td>
                        <td>
                            <?$visible = OCModel::getVisibilityForEpisode($course_id, $episode->mediapackage->id); ?>
                            <? if($visible['visible'] == 'true') : ?>
                                <?=_("Sichtbar")?>
                            <? else : ?>
                                <?=_("Unsichtbar")?>
                            <? endif; ?>
                        </td>

                        <td>
                            <a href="<?=PluginEngine::getLink('opencast/course/toggle_visibility/'.$episode->mediapackage->id ) ?>">
                                <?= Assets::img('icons/16/blue/visibility-visible.png', array('title' => _("Aufzeichnung unsichtbar schalten"))) ?>
                            </a>
                        </td>
                    </tr>
                    <? endif ;?>
                <? endforeach; ?>

       </table>
    <? endif;?>
<? endif; ?>

<?$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content); ?>
