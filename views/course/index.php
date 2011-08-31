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

<?
$items = $episode_ids;
?>

<div name='content_list' style='padding:15px;'>
    <table width="100%">
        <tr>
            <td width="400" class="blank" style="vertical-align:top;">
                <? if ($embed) : ?>
                    <iframe src="http://<?=$embed?>"
                    	 style="border:0px #FFFFFF none;" name="Opencast Matterhorn - Media Player" scrolling="no" frameborder="1" marginheight="0px" marginwidth="0px" width="460" height="468"></iframe>
                    <? foreach ($items as $item) :?>
                        <? if ($item['id'] == $active_id) : ?>
                                        <?= $item['title'] ? '<b>'. $item['title'] .'</b><br/>' : ''?>
                            <?=$item['description'] ? $item['description']  : 'Keine Beschreibung vorhanden';?>
                        <? endif; ?>
                    <? endforeach; ?>
                <? endif; ?>
            </td>
            <td width="100%" class="blank" valign="top" rowspan=3>
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <? if (sizeof($items) == 0) : ?>
                    <?= MessageBox::info(_("Es wurden bisher keine Aufzeichnungen bereitgestellt.")) ?>
                <? else : foreach($items as $item) :
                    echo "<!-- start -->";
                ?>
                <? if ($active_id == $item['id']) : ?>
            <tr>
                <td class="printhead" style="padding:1px; vertical-align: absmiddle" height="25" width="70%">
                    <img src="<?=Assets::image_path("icons/16/grey/arr_1down.png")?>" align="absmiddle">
                    <a href="<?= PluginEngine::getLink('opencast/course/index/'. $item['id']) ?>">
                        <?= mb_convert_encoding($item['title'], 'ISO-8859-1', 'UTF-8') ?>
                    </a>
                </td>
                <td class="printhead" width="10%">
                    &nbsp;
                    <?=    '<b>'. $item['date'] .'</b>'; ?>
                </td>
            </tr>
            <tr>
                <td class="printcontent" valign="center" style="padding-left: 10px" height="25" colspan="3">
                    <? if ($item['author']) : ?>
                    <p>
                        <?= _("Autor") ?>: <i><?=$item['author'] ?></i><br>
                    </p>
                    <? endif; ?>
                    <? if ($item['start']) : ?>
                    <p>
                        <? date_default_timezone_set('Europe/Lisbon');?>
                        <?= _("Aufzeichnungsdatum") ?>: <i><?=date("d.m.Y H:m",strtotime($item['start']));?> </i><br>
                    </p>
                    <? endif; ?>
                    <p>
                        <?= $item['description']  ? $item['description'] : 'Keine Beschreibung vorhanden'; ?>
                    </p>
                    <center>

                    </center>
                    <br/>
                </td>
            </tr>
            <tr>    
                <td class='steelgrau'>&nbsp;</td>
            </tr>
            <? else : ?>
                <td class="printhead" style="padding:1px; vertical-align: absmiddle" height="25" width="70%">
                    &nbsp;<img src="<?=Assets::image_path("icons/16/grey/arr_1right.png")?>" align="abstop">
                    <a href="<?= PluginEngine::getLink('opencast/course/index/'. $item['id']) ?>">
                        <?= mb_convert_encoding($item['title'], 'ISO-8859-1', 'UTF-8')?>
                    </a>
                </td>
                <td class="printhead" width="10%">
                    &nbsp; 
                    <? if (($content['timestamp'] < $current_semester['beginn']) && $content['semester']) :
                        echo  '<b>'. $content['semester'] .'</b>';
                    endif; ?>
                </td>

            </tr>
            <? endif;
                echo "<!-- ende -->";
        endforeach;
        endif;
        ?>
        </table>
    </td>
    </tr>

    <tr><td class="blank" ></td></tr>


</table>
</div>