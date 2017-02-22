<? if($flash['delete']) : ?>
    <?= createQuestion2(sprintf(_('Wollen Sie die Verknüpfung zur Series "%s" wirklich aufheben?'), utf8_decode($this->connectedSeries[0]['title'])),  array('course_id' => $course_id, 'series_id' => $this->connectedSeries[0]['identifier'], 'delete' => true),array('cancel' => true),PluginEngine::getLink('opencast/course/remove_series/'. get_ticket()))?>

<? endif ?>

<?= $this->render_partial('messages') ?>

<script language="JavaScript">
    STUDIP.hasperm  = <?=var_export($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id))?>;
    OC.states = <?=json_encode($states)?>;
    OC.initIndexpage();
    OC.initUpload(<?= OC_UPLOAD_CHUNK_SIZE ?>);
</script>

<?
    $sidebar = Sidebar::get();
    
    if($GLOBALS ['perm']->have_studip_perm ('dozent', $this->course_id))
    {
        $actions = new ActionsWidget ();
        $upload = '';
        if(! empty ($connectedSeries))
        {
            $actions->addLink(_("Verknüpfung aufheben"), PluginEngine::getLink ('opencast/course/remove_series/' . get_ticket()), 'icons/16/blue/trash.png');
            $actions->addLink(_("Episodenliste aktualisieren"), PluginEngine::getLink ('opencast/course/refresh_episodes/' . get_ticket()), 'icons/16/blue/refresh.png');
            $actions->addLink(_("Medien hochladen"), '#', 'icons/16/blue/upload.png', array (
                'id' => 'oc_upload_dialog'
            ));
            if($series_metadata [0] ['schedule'] == '1')
            {
                $actions->addLink(_("Workflow konfigurieren"), '#', 'icons/16/blue/admin.png', array('id' => 'oc_workflow_dialog'));

            }

        } else
        {
            $actions->addLink(_('Neue Series anlegen'), PluginEngine::getLink ('opencast/course/create_series/'), 'icons/16/blue/tools.png');
            $actions->addLink(_('Vorhandene Series verknüpfen'), '#', 'icons/16/blue/group.png', array (
                    'id' => 'oc_config_dialog' 
           ));
        }


        //todo - should this already be visibile for teachers?
        if($coursevis == 'visible'){
            $actions->addLink(_("Reiter verbergen"), PluginEngine::getLink ('opencast/course/toggle_tab_visibility/' . get_ticket()), 'icons/16/blue/visibility-visible.png');
        } else {
            $actions->addLink(_("Reiter anzeigen"), PluginEngine::getLink ('opencast/course/toggle_tab_visibility/' . get_ticket()), 'icons/16/blue/visibility-invisible.png');
        }

        $sidebar->addWidget ($actions);
        Helpbar::get ()->addPlainText ('', _("Hier sehen Sie eine Übersicht ihrer Vorlesungsaufzeichnungen. Sie können über den Unterpunkt Aktionen weitere Medien zur Liste der Aufzeichnungen hinzufügen. Je nach Größe der Datei kann es einige Zeit in Anspruch nehmen, bis die entsprechende Aufzeichnung in der Liste sichtbar ist. Weiterhin ist es möglich die ausgewählten Sichtbarkeit einer Aufzeichnung innerhalb der Veranstaltung direkt zu ändern."));
    } else
    {
        Helpbar::get ()->addPlainText ('', _("Hier sehen Sie eine Übersicht ihrer Vorlesungsaufzeichnungen."));
    }
?>




<h1>
  <?= _('Vorlesungsaufzeichnungen') ?>
</h1>

<? if(!(empty($ordered_episode_ids))) : ?>

<? foreach($ordered_episode_ids as $oe) :?>
    <? if($oe['id'] == $active_id) :?>
         <? $active = $oe;?>
    <? endif;?>
<? endforeach;?>


<? $visible = OCModel::getVisibilityForEpisode($course_id, $active['id'])?>
<div class="oc_flex">

    <div class="oc_flexitem oc_flexplaycontainer" >
        <div id="oc_balls" class="la-ball-scale-ripple-multiple la-3x">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="oce_playercontainer">
            <span id="oc_active_episode" class="hidden" data-activeepisode="<?=$active['id']?>">
            </span>
            <? if($theodul) : ?>
                <iframe src="<?=$embed?>"
                        style="border:0px #FFFFFF none;"
                        name="Opencast Matterhorn video player"
                        scrolling="no"
                        frameborder="0"
                        marginheight="0px"
                        marginwidth="0px"
                        width="720"
                        height="360"
                        allowfullscreen="true"
                        webkitallowfullscreen="true"
                        mozallowfullscreen="true">
                </iframe>
            <? else: ?>
                <iframe class="oc_playerframe" src="<?=$embed?>&hideControls=false"
                    style="border: 0px #FFFFFF none;"
                    name="Opencast Matterhorn - Media Player" scrolling="no"
                    frameborder="0" marginheight="0px" marginwidth="0px"  height="250px">
                </iframe>
             <? endif; ?>
            <br>
            <div class="oce_emetadata">
                <h2 class="oce_title"><?= $active['title']?></h2>
                <ul class="oce_contetlist">
                    <li><?=_('Aufzeichnungsdatum : ')?> <?=date("d.m.Y H:m",strtotime($active['start']));?> <?=_("Uhr")?></li>
                    <li><?=_('Autor : ')?> <?=$active['author'] ? $active['author']  : 'Keine Angaben vorhanden';?></li>
                    <li><?=_('Beschreibung : ')?> <?=$active['description'] ? $active['description']  : 'Keine Beschreibung vorhanden';?></li>
                </ul>
                <div class="ocplayerlink">
                    <div style="text-align: left; font-style: italic;">Weitere
                        Optionen:</div>
                    <div class="button-group">
                        <?
                        if (get_config('OPENCAST_EXTENDED_PLAYER_BUTTON')) {
                            print(Studip\LinkButton::create(_('Erweiterter Player'), URLHelper::getURL('http://' . $engage_player_url), array('target' => '_blank', 'class' => 'ocextern')));
                        }
                        ?>
                        <? if($active['presenter_download']) : ?>
                            <?= Studip\LinkButton::create(_('ReferentIn'), URLHelper::getURL($active['presenter_download']), array('target'=> '_blank', 'class' => 'download presenter')) ?>
                        <? endif;?>
                        <? if($active['presentation_download']) : ?>
                            <?= Studip\LinkButton::create(_('Bildschirm '), URLHelper::getURL($active['presentation_download']), array('target'=> '_blank', 'class' => 'download presentation')) ?>
                        <? endif;?>
                        <? if($active['audio_download']) :?>
                            <?= Studip\LinkButton::create(_('Audio'), URLHelper::getURL($active['audio_download']), array('target'=> '_blank', 'class' => 'download audio')) ?>
                        <? endif;?>
                        </div>
                        <? if($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) :?>
                        <div class="button-group" style="float:right">
                            <? if ($visible && $visible['visible'] == 'false') : ?>
                                <?= Studip\LinkButton::create(_('Aufzeichnung unsichtbar'), PluginEngine::getLink('opencast/course/toggle_visibility/' . $active_id .'/'. $active['position']), array('class' => 'ocinvisible ocspecial', 'id' => 'oc-togglevis', 'data-episode-id' => $active_id, 'data-position' => $active['position'])); ?>
                            <? else : ?>
                                <?= Studip\LinkButton::create(_('Aufzeichnung sichtbar'), PluginEngine::getLink('opencast/course/toggle_visibility/' . $active_id .'/'. $active['position']), array('class' => 'ocvisible ocspecial', 'id' => 'oc-togglevis', 'data-episode-id' => $active_id,'data-position' => $active['position'])); ?>
                            <? endif; ?>

                        </div>
                        <? endif;?>
                    </div>
            </div>
        </div>
    </div>
    <div id="episodes" class="oc_flexitem oc_flexepisodelist">
        <span class="oce_episode_search">
            <input class="search" placeholder="<?=_('Nach Aufzeichung suchen')?>" size="30" />
            <img class="sort" data-sort="name" src="<?=Assets::image_path('icons/16/blue/search.png')?>">
        </span>
        </img>
        <ul class="oce_list list"
            <?=($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) ? 'id="oce_sortablelist"' : ''?>>
            <? if($GLOBALS['perm']->have_studip_perm('dozent', $course_id) && !empty($states)) :?>
                <? foreach($states as $workflow_id => $state) :?>
                <li class="uploaded oce_item" style="position: relative;">

                    <? if($state->state == 'FAILED') : ?>
                        <div class="oce_preview_container">
                            <?=_("Videoverarbeitung fehlerhaft")?>
                        </div>
                <div class="oce_metadatacontainer oce_failedstate">
                    <h3 class="oce_metadata"><?= htmlready(mb_convert_encoding($state->mediapackage->title, 'ISO-8859-1', 'UTF-8'))?></h3>
                            <?= Studip\LinkButton::create(_('Daten vom Server entfernen'), PluginEngine::getLink('opencast/course/remove_failed/' . $state->id)); ?></span>
                </div>
                    <? else :?>
                    <div class="oce_preview_container"  style="max-height: 96px; display: inline; max-width: 120px; position: absolute; top: 4px; cursor: default;">
                        <div id="<?=$workflow_id?>" class="workflow_info" style="max-width: 120px;display: inline;">
                            <strong style="font-size: 9pt;position: absolute; top:22px; left: 15px; max-width:50px;line-height: 17px;text-align: center"></strong>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                    <div class="oce_metadatacontainer">
                        <h3 class="oce_metadata"><?=_('Video wird verarbeitet: ')?> <?= htmlready(mb_convert_encoding($state->mediapackage->title, 'ISO-8859-1', 'UTF-8'))?></h3>
                        <span class="oce_metadata"><?=sprintf(_("Hochgeladen am %s"),date("d.m.Y H:m",strtotime($state->mediapackage->start)))?></span>
                    </div>
                    <? endif; ?>
            </li>
                <? endforeach;?>
            <? endif;?>
            <? foreach($ordered_episode_ids as $pos => $item) : ?>
            <li id="<?=$item['id']?>"
                class="<?=($item['visibility'] != 'false') ? 'oce_item' : 'hidden_ocvideodiv oce_item'?><?=($item['id'] == $active['id']) ? ' oce_active_li' : ''?>"
                data-courseId="<?=$course_id?>"
                data-visibility="<?=$item['visibility']?>"
                data-pos="<?=$pos?>"
                data-mkdate="<?=$item['mkdate']?>">
                <a
                href="<?= PluginEngine::getLink('opencast/course/index/'. $item['id']) ?>">
                    <div>
                        <img
                            class="oce_preview <?=($item['visibility'] == false) ? 'hidden_ocvideo' : ''?>"
                            src="<?=$item['preview']?>">
                    </div>
                    <div class="oce_metadatacontainer">
                        <h3 class="oce_metadata oce_list_title"><?= $item['title']?> <?=($item['visibility'] != 'false') ? '' : ' (Unsichtbar)'?></h3>
                        <span class="oce_list_date"><?=sprintf(_("Vom %s"),date("d.m.Y H:m",strtotime($item['start'])))?></span>
                    </div>
            </a>
            </li>
            <? endforeach; ?>
        </ul>
    </div>
</div>
<? else: ?>
    <? if(empty($this->connectedSeries) && $GLOBALS['perm']->have_studip_perm('dozent', $course_id)) :?>
            <?= MessageBox::info(_("Sie haben noch keine Series aus Opencast mit dieser Veranstaltung verknüpft. Bitte erstellen Sie eine neue Series oder verknüpfen eine bereits vorhandene Series.")) ?>
    <? else: ?>
        <?=MessageBox::info(_('Es wurden bislang keine Vorlesungsaufzeichnungen bereitgestellt.'));?>
    <? endif;?>
<? endif; ?>


<? if($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) :?>

<div id="upload_dialog" title="<?=_("Medienupload")?>">
<?= $this->render_partial("course/_upload", array('course_id' => $course_id, 'dates' => $dates, 'series_id' => $this->connectedSeries[0]['identifier'])) ?>
</div>

<div id="config_dialog" title="<?=_("Series verknüpfen")?>">
    <?= $this->render_partial("course/_config", array()) ?>
</div>

<div id="workflow_dialog" title="<?=_("Workflow-Konfiguration")?>">
    <?= $this->render_partial("course/_workflowselection", array('workflows' => $tagged_wfs)); ?>
</div>
<? endif;?>

<!--- hidden -->
<div class="hidden" id="course_id" data-courseId="<?=$course_id?>"></div>
<?= $this->render_partial("course/_playerfragment", array("extendedPlayerButton" => get_config("OPENCAST_EXTENDED_PLAYER_BUTTON"))) ?>
<?= $this->render_partial("course/_episodelist", array()) ?>
