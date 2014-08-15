<? if (isset($this->flash['error'])): ?>
    <?= MessageBox::error($this->flash['error']) ?>
<? endif ?>
<? if($upload_message) :?>
    <?= MessageBox::success(_('Die Datei wurden erfolgreich hochgeladen. Je nach Größe der Datei und Auslastung des Opencast Matterhorn-Server kann es einige Zeit in Anspruch nehmen, bis die entsprechende Aufzeichnung in der Liste sichtbar wird.')); ?>
<? endif;?>

<script language="JavaScript">
    OC.initIndexpage();
    OC.initUpload(<?= OC_UPLOAD_CHUNK_SIZE ?>);
</script>

<?
if($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
    $aktionen = array();
    $aktionen[] = array(
                  "icon" => "icons/16/black/upload.png",
                  "text" => '<a id="oc_upload_dialog"href="#">' . _("Medien hochladen") . '</a>');

    $infobox_content = array(array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(array(
            'icon' => 'icons/16/black/info.png',
            'text' => _("Hier sehen Sie eine Übersicht ihrer Vorlesungsaufzeichnungen. Sie können über den Unterpunkt Aktionen weitere Medien zur Liste der Aufzeichnungen hinzufügen. Je nach Größe der Datei kann es einige Zeit in Anspruch nehmen, bis die entsprechende Aufzeichnung in der Liste sichtbar ist. Weiterhin ist es möglich die ausgewählten Sichtbarkeit einer Aufzeichnung innerhalb der Veranstaltung direkt zu ändern.")
        )
    ), ),
        array("kategorie" => _("Aktionen:"),
              "eintrag"   => $aktionen
        ));

    $infobox = array('picture' => 'infobox/lectures.jpg', 'content' => $infobox_content);
}
?>




<h1>
  <?= _('Vorlesungsaufzeichnungen') ?>
</h1>
<? if(!(empty($episode_ids))) : ?>

<? $active = $episode_ids[$active_id]?>
<? $visible = OCModel::getVisibilityForEpisode($course_id, $active['id'])?>
    <div class="oce_playercontainer">
        <iframe src="http://<?=$embed?>&hideControls=false" style="border:0px #FFFFFF none;" name="Opencast Matterhorn - Media Player" scrolling="no" frameborder="0" marginheight="0px" marginwidth="0px" width="100%" height="250px"></iframe><br>
        <div class="oce_emetadata">
            <h2 class="oce_title"><?= mb_convert_encoding($active['title'], 'ISO-8859-1', 'UTF-8')?></h2>
            <ul class="oce_contetlist">
                <li><?=_('Aufzeichnungsdatum : ')?> <?=date("d.m.Y H:m",strtotime($active['start']));?> <?=_("Uhr")?></li>
                <li><?=_('Autor : ')?> <?=$active['author'] ? mb_convert_encoding($active['author'], 'ISO-8859-1', 'UTF-8')  : 'Keine Angaben vorhanden';?></li>
                <li><?=_('Beschreibung : ')?> <?=$active['description'] ? mb_convert_encoding($active['description'], 'ISO-8859-1', 'UTF-8')  : 'Keine Beschreibung vorhanden';?></li>
            </ul>
            <div class="ocplayerlink">
                <div style="text-align:left; font-style:italic;">Weitere Optionen:</div>
                <div class="button-group">
                <?= Studip\LinkButton::create(_('Erweiterter Player'), URLHelper::getURL('http://'.$engage_player_url), array('target'=> '_blank','class' => 'ocextern')) ?>
                <?= Studip\LinkButton::create(_('Download ReferentIn'), URLHelper::getURL($active['presenter_download']), array('target'=> '_blank', 'class' => 'download presenter')) ?>
                <?= Studip\LinkButton::create(_('Download Bildschirm '), URLHelper::getURL($active['presentation_download']), array('target'=> '_blank', 'class' => 'download presentation')) ?>
                <?= Studip\LinkButton::create(_('Download Audio'), URLHelper::getURL($active['audio_download']), array('target'=> '_blank', 'class' => 'download audio')) ?>
                </div>
                <? if($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) :?>
                <div class="button-group">
                    <? if ($visible && $visible['visible'] == 'false') : ?>
                        <?= Studip\LinkButton::create(_('Aufzeichnung sichtbar schalten'), PluginEngine::getLink('opencast/course/toggle_visibility/' . $active_id), array('class' => 'ocinvisible ocspecial')); ?>
                    <? else : ?>
                        <?= Studip\LinkButton::create(_('Aufzeichnung unsichtbar schalten'), PluginEngine::getLink('opencast/course/toggle_visibility/' . $active_id), array('class' => 'ocvisible ocspecial')); ?>
                    <? endif; ?>
                   
                </div>
                <? endif;?>
            </div>
        </div>
    </div>
    
    <div id="episodes">
    <ul class="oce_list">
        <? foreach($episode_ids as $item) : ?>
        <li>
            <a href="<?= PluginEngine::getLink('opencast/course/index/'. $item['id']) ?>">
            <div class="<?=($item['visibility'] == false) ? 'hidden_ocvideodiv' : ''?>"><img class="oce_preview <?=($item['visibility'] == false) ? 'hidden_ocvideo' : ''?>" src="<?=$item['preview']?>"></div>
            <h3 class="oce_metadata"><?= mb_convert_encoding($item['title'], 'ISO-8859-1', 'UTF-8')?></h3>
            <span class="oce_metadata"><?=sprintf(_("Vom %s"),date("d.m.Y H:m",strtotime($item['start'])))?></span>
            </a>
        </li>
        <? endforeach; ?>
    </ul>
    </div>
<? else: ?>
    <?=MessageBox::info(_('Es wurden bislang keine Vorlesungsaufzeichnungen bereitgestellt.'));?>
<? endif; ?>

<div id="upload_dialog" title="<?=_("Medienupload")?>">
<?= $this->render_partial("course/_upload", array('course_id' => $course_id, 'dates' => $dates)) ?>
</div>