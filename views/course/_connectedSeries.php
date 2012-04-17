<div>
        <? if (false && sizeof($cseries) == 0) : ?>
            <?= MessageBox::info(_("Es sind bislang noch keine Series verfügbar. Bitte überprüfen Sie die globalen Opencast Matterhorn Einstellungen.")) ?>
        <? else : ?>
        <form action="<?= PluginEngine::getLink('opencast/course/edit/'.$course_id) ?>"
            method=post>
                    <div style="text-align: center;">
                    <p> <?//=_("Series ohne Zuordnung")?></p>
                    <? if(!empty ($rseries)) :?>
                    <select class="series_select" multiple="multiple" name="series[]">

                        <? foreach($rseries->series as $serie) :?>
                            <? if(isset($serie->id)) : ?>

                                <option value="<?=$serie->id?>">
                                    <?= OCModel::getMetadata($serie->additionalMetadata, 'title')?>
                                    <?//= $series->additionalMetadata?>
                                </option>
                            <? endif ?>
                        <? endforeach ?>
                    </select>
         <? endif ?>
</div>


            <!--

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

            
                    <select multiple="multiple" name="series[]">
                        <? foreach($rseries->seriesList as $serie) :?>
                            <? $s = $series_client->getSeries($serie->id); ?>
                            <? if($s->series->additionalMetadata !=null) : ?>
                                <option value="<?=$serie->id?>">
                                    <?=  OCModel::getMetadata($s->series->additionalMetadata, 'title')?>
                                </option>
                            <? endif ?>
                        <? endforeach ?>
                    </select>
                    <? endif ?>
                </div>
            </div> -->
            <div style="padding-top:2em;clear:both" class="form_submit">
                <?= makebutton("uebernehmen","input") ?>
                <a href="<?=PluginEngine::getLink('opencast/course/index')?>"><?= makebutton("abbrechen")?></a>
            </div>
        </form>
        <? endif;?>
    </div>