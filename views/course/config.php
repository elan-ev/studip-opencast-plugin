<? if (isset($message)): ?>
  <?= MessageBox::success($message) ?>
<? endif ?>

<?
$infobox_content = array(array(
    'kategorie' => _('Hinweise:'),
    'eintrag'   => array(array(
        'icon' => 'icons/16/black/info.png',
        'text' => _("Hier können die eingebundenen Vorlesungsaufzeichnungen aus dem angebundenen Opencast Matterhorn System verwaltet werden. Sie können Series, die noch keiner Veranstaltung zugeordnet sind der aktuellen Verantstaltung zuordnen. Wenn Sie bestehende Zuordnungen löschen möchten, klichen Sie auf den jeweiligen Mülleimer.")
    ))
));

$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>
<h1><?=_('Verwalten Sie hier die eingebundenen Vorlesungsaufzeichnungen:')?></h1>
<form action="<?= PluginEngine::getLink('opencast/course/edit/'.$course_id) ?>"
    method=post>
    <p><?=_('Wählen Sie hier eine Series aus, die Sie mit der aktuellen Veranstaltung verknüpfen möchten')?>:</p>
    <div style="dislay:inline;vertical-align:middle">
        <div style="float:left;">
            <p><?=_("Zugeordnete Series")?></p>
            <ul style="list-style-type: none;margin:0;padding-left:50px">
                <? if(is_array($cseries)) :?>
                <? foreach($cseries as $serie) :?>
                    <li style="padding-top:5px">
                        <? $s = $occlient->getSeries($serie['series_id']); ?>
                        <?=mb_convert_encoding($s->metadataList->metadata[6]->value, 'ISO-8859-1', 'UTF-8')?>
                        <a href="<?=PluginEngine::getLink('opencast/course/remove_series/'.$course_id.'/'.$serie['series_id'])?>">
                            <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Zuordnung löschen'), 'alt' => _('Zuordnung löschen')))?>
                        </a>
                    </li>
                <? endforeach ?>
                <? else : ?>
                    <p><?=_("Bislang wurde noch keine Series zugeordnet.")?></p>
                <? endif?>
            </ul>
        </div>
        <div style="text-align:center">
            <p> <?=_("Series ohne Zuordnung")?></p>
            <select multiple="multiple" name="series[]" size="10">
                              <? foreach($series as $serie) :?>
                                  <? $s = $occlient->getSeries($serie['series_id']); ?>
                                  <? if($s->metadataList->metadata[6]->value !=null) : ?>
                                  <option value="<?=$serie['series_id']?>"><?=mb_convert_encoding($s->metadataList->metadata[6]->value, 'ISO-8859-1', 'UTF-8')?></option>
                                  <? endif ?>
                              <? endforeach ?>
            </select>
        </div>
    </div>
    <div style="padding-top:2em;clear:both" class="form_submit">
        <?= makebutton("uebernehmen","input") ?>
        <a href="<?=PluginEngine::getLink('opencast/course/index')?>"><?= makebutton("abbrechen")?></a>
    </div>
</form>