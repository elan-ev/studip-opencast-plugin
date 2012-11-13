<?
$infobox['picture'] = 'infobox/studygroup.jpg';
$infobox['content'] = array(
array(
        'kategorie'=>_("Information"),
        'eintrag'=>array(
array(
      'text' => _("Hier finden Sie die Vorlesungsaufzeichnungen dieser Veranstaltung. Klicken Sie auf einen Titel in der Liste um die Wiedergabe zu starten."),
                'icon' => 'icons/16/black/info.png'
                )
                )
                ),
                );
?>



<h3>
  <?= _('Vorlesungsaufzeichnungen') ?>
</h3>

<? if(!(empty($episode_ids))) : ?>
    <div name='content_list' style='padding:15px;'>
        <table width="100%">
            <tr>
                <td class="blank" style="vertical-align: top; text-align: center;" width="60%">
                    <iframe src="http://<?=$embed?>" style="border:0px #FFFFFF none;" name="Opencast Matterhorn - Media Player" scrolling="no" frameborder="0" marginheight="0px" marginwidth="0px" width="540" height="404"></iframe><br>

                </td>

                <td class="blank serieslist"  rowspan="3" valign="top" width="100%">
                    <div class="listwrapper" id="list">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                            <? foreach($episode_ids as $item) : ?>
                            <? if ($active_id == $item['id']) : ?>
                            <tr>
                                <td class="ocphead" style="padding: 1px;" width="auto" height="25">
                                    <img src="<?=Assets::image_path("icons/16/grey/arr_1down.png")?>" align="absmiddle">
                                            <a href="<?= PluginEngine::getLink('opencast/course/index/'. $item['id']) ?>">
                                        <?= mb_convert_encoding($item['title'], 'ISO-8859-1', 'UTF-8') ?>
                                    </a>
                                </td>
                             </tr>
                             <tr>
                                <td class="occontent">
                                    <ul class="occontetlist">
                                        <li><?=_('Aufzeichnungsdatum : ')?> <?=date("d.m.Y H:m",strtotime($item['start']));?> <?=_("Uhr")?></li>
                                        <li><?=_('Autor : ')?> <?=$item['author'] ? mb_convert_encoding($item['author'], 'ISO-8859-1', 'UTF-8')  : 'Keine Angaben vorhanden';?></li>
                                        <li><?=_('Beschreibung : ')?> <?=$item['description'] ? mb_convert_encoding($item['description'], 'ISO-8859-1', 'UTF-8')  : 'Keine Beschreibung vorhanden';?></li>
                                    </ul>
                                    <div class="ocplayerlink">
                                        <div style="text-align:left; font-style:italic;">Online schauen:</div>
                                        <?= Studip\LinkButton::create(_('Erweiterter Player'), $engage_player_url, array('class' => 'oc_tooltip',  'target'=> '_blank')) ?>
                                    </div>
                                    <div class="download" style="visibility: hidden;">
                                    </div>
                                </td>
                            </tr>
                            <? else : ?>
                            <tr>
                                <td class="ocphead" style="padding: 1px;" height="25">
                                    <img src="<?=Assets::image_path("icons/16/grey/arr_1right.png")?>" align="absmiddle">
                                    <a href="<?= PluginEngine::getLink('opencast/course/index/'. $item['id']) ?>">
                                        <?= mb_convert_encoding($item['title'], 'ISO-8859-1', 'UTF-8') ?>
                                    </a>
                                </td>
                            </tr>
                            <? endif; ?>
                            <? endforeach; ?>
                    </table>
                </div>
                </td>
                </tr>
        </table>
    </div>


    <script>
        jQuery(document).ready(function init_indexpage() {
            // tooltips
            jQuery(".oc_tooltip").tipTip({
                defaultPosition: "left",
                keepAlive: false,
                maxWidth: 270,
                edgeOffset: 8,
                delay: 50,
                content: "<h3>Opencast Matterhorn Player</h3><div style='text-align:center;'><img src='"+ STUDIP.ABSOLUTE_URI_STUDIP +"plugins_packages/elan-ev/OpenCast/images/online-prev.png' /></div><p>Klicken Sie hier, um zu dem Vollbildplayer mit beiden<br />Video-Streams zu gelangen.</p>"
            });
            jQuery(".oc_tooltip").tipTip({
                defaultPosition: "top", edgeOffset: 3, delay: 50
            });
            // Slimscroll
            jQuery('#list').slimScroll({
                height: '400px',
                railVisible: true,
                alwaysVisible: false,
                distance: '3px'
            })
        });

    </script>
<? else: ?>
    <?=MessageBox::info(_('Es wurden bislang keine Vorlesungsaufzeichnungen bereitgestellt.'));?>
<? endif; ?>
