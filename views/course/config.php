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
<script language="JavaScript">
OC.initAdmin();
</script>
<h3><?=_('Verwaltung der eingebundenen Vorlesungsaufzeichnungen')?></h3>
<div id="admin-accordion">
    <h3><?=_('Veranstaltungsaufzeichnungen Planen')?>:</h3>
    <div class="oc_schedule">
        <h4>Ablaufplan</h4>
        <p>Erst feststellen ob ein Raum mit CA da ist und dann den Ablaufplan posten mit Checkboxes zum Schedulen...</p>
        <?
        /*
            foreach($dates['regular'] as $reg_date) {
                var_dump($reg_date);
            }
        */
       //var_dump($termine);
         ?>
    </div>


    <h3><?=_('Wählen Sie rechts eine Series aus, die Sie mit der aktuellen Veranstaltung verknüpfen möchten')?>:</h3>
    <div>
        <? if (sizeof($series) == 0) : ?>
            <?= MessageBox::info(_("Es sind bislang noch keine Series verfügbar. Bitte überprüfen Sie die globalen Opencast Matterhorn Einstellungen.")) ?>
        <? else : ?>
        <form action="<?= PluginEngine::getLink('opencast/course/edit/'.$course_id) ?>"
            method=post>
            <div style="dislay:inline;vertical-align:middle">
                <div style="float:left;">
                    <p><?//=_("Zugeordnete Series")?></p>
                    <ul style="list-style-type: none;margin:0;padding-left:50px">
                        <? if(!empty($cseries)) :?>
                        <? foreach($cseries as $serie) :?>
                            <li>
                                <? $s = $series_client->getSeries($serie['series_id']); ?>
                                <?=  OCModel::getMetadata($s->series->additionalMetadata, 'title')?>
                                <a href="<?=PluginEngine::getLink('opencast/course/remove_series/'.$course_id.'/'.$serie['series_id'])?>">
                                    <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Zuordnung löschen'), 'alt' => _('Zuordnung löschen')))?>
                                </a>
                            </li>
                        <? endforeach ?>
                        <? else : ?>
                            <li style="padding-top:5px">
                                <p><?=_("Bislang wurde noch keine Series zugeordnet.")?></p>
                            </li>
                        <? endif ?>
                    </ul>
                </div>
                <div style="float:center;text-align:center">
                    <p> <?//=_("Series ohne Zuordnung")?></p>
                    <? if(!empty ($rseries) || true) :?>


                    <select multiple="multiple" name="series[]" size="10">
                        <? foreach($rseries as $serie) :?>
                            <? $s = $series_client->getSeries($serie['series_id']); ?>
                            <? if($s->series->additionalMetadata !=null) : ?>
                                <option value="<?=$serie['series_id']?>">
                                    <?=  OCModel::getMetadata($s->series->additionalMetadata, 'title')?>
                                </option>
                            <? endif ?>
                        <? endforeach ?>
                    </select>
                    <? endif ?>
                </div>
            </div>
            <div style="padding-top:2em;clear:both" class="form_submit">
                <?= makebutton("uebernehmen","input") ?>
                <a href="<?=PluginEngine::getLink('opencast/course/index')?>"><?= makebutton("abbrechen")?></a>
            </div>
        </form>
        <? endif;?>
    </div>
</div>