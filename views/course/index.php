<? if (isset($this->flash['error'])): ?>
    <?= MessageBox::error($this->flash['error']) ?>
<? endif ?>
<script language="JavaScript">
    OC.initEpisodelist();
</script>
<h1>
  <?= _('Vorlesungsaufzeichnungen') ?>
</h1>
<? if(!(empty($episode_ids))) : ?>

<? $active = $episode_ids[$active_id]?>
    <div class="oce_playercontainer">
        <iframe src="https://<?=$embed?>&hideControls=false" style="border:0px #FFFFFF none;" name="Opencast Matterhorn - Media Player" scrolling="no" frameborder="0" marginheight="0px" marginwidth="0px" width="100%" height="250px"></iframe><br>
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
            </div>
        </div>
    </div>
    
    <div id="episodes">
    <ul class="oce_list">
        <? foreach($episode_ids as $item) : ?>
        <li>
            <a href="<?= PluginEngine::getLink('opencast/course/index/'. $item['id']) ?>">
            <img class="oce_preview" src="<?=$item['preview']?>"></span>
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
