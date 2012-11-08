<div>
        <? if (false && sizeof($connectedSeries) == 0) : ?>
            <?= MessageBox::info(_("Es sind bislang noch keine Series verfügbar. Bitte überprüfen Sie die globalen Opencast Matterhorn Einstellungen.")) ?>
        <? else : ?>
        <form action="<?= PluginEngine::getLink('opencast/course/edit/'.$course_id) ?>"
            method=post>
                    <div style="text-align: center;">
                    <p> <?//=_("Series ohne Zuordnung")?></p>
                    <? if(!empty ($unconnectedSeries)) :?>
                    <select class="series_select" multiple="multiple" name="series[]">
                        <? $x = 'http://purl.org/dc/terms/'; ?>
                        <? foreach($rseries as $serie) :?>
                            <? if(isset($serie->$x->identifier[0]->value)) : ?>

                                <option value="<?=$serie->$x->identifier[0]->value?>">
                                    <?= $serie->$x->title[0]->value?>
                                    <?//= $series->additionalMetadata?>
                                </option>
                            <? endif ?>
                        <? endforeach ?>
                    </select>
         <? endif ?>
</div>


            <div style="dislay:inline;vertical-align:middle">
                <div style="float:left;">
                    <p><?=_("Zugeordnete Series")?></p>
                    <ul style="list-style-type: none;margin:0;padding-left:50px">
                        <? if(!empty($connectedSeries)) :?>
                        <? foreach($connectedSeries as $serie) :?>
                            <li>
                                <?=  $serie['title']?>
                                <a href="<?=PluginEngine::getLink('opencast/course/remove_series/'.$course_id.'/'.$serie['identifier'])?>">
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
                    <? if(!empty ($notConnectedSeries) || true) :?>

            
                    <select multiple="multiple" name="series[]">
                        <? foreach($notConnectedSeries as $serie) :?>
                            <? if($s->series->additionalMetadata !=null) : ?>
                                <option value="<?= $serie['identifier'] ?>">
                                    <?= $serie['title'] ?>
                                </option>
                            <? endif ?>
                        <? endforeach ?>
                    </select>
                    <? endif ?>
                </div>
            </div> 
            <div style="padding-top:2em;clear:both" class="form_submit">
                <?= \Studip\Button::createAccept(_('Übernehmen')) ?>
                <?= \Studip\LinkButton::createCancel(_('Abbrechen'),PluginEngine::getLink('opencast/course/index')) ?>
            </div>
        </form>
        <? endif;?>
    </div>
