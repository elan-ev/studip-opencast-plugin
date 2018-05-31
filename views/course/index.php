<? if($flash['delete']) : ?>
    <?= createQuestion2(sprintf($_('Wollen Sie die Verknüpfung zur Series "%s" wirklich aufheben?'), utf8_decode($this->connectedSeries[0]['title'])),  array('course_id' => $course_id, 'series_id' => $this->connectedSeries[0]['identifier'], 'delete' => true),array('cancel' => true),PluginEngine::getLink('opencast/course/remove_series/'. get_ticket()))?>

<? endif ?>

<?= $this->render_partial('messages') ?>

<script language="JavaScript">
    STUDIP.hasperm  = <?=var_export($GLOBALS['perm']->have_studip_perm('tutor', $this->course_id))?>;
    OC.states = <?=json_encode($states)?>;
    OC.initIndexpage();
    <?  if($series_metadata[0]['schedule'] == '1') : ?>
        OC.initUpload(<?= OC_UPLOAD_CHUNK_SIZE ?>);
    <? endif; ?>
</script>

<?
    $sidebar = Sidebar::get();

    if($GLOBALS ['perm']->have_studip_perm ('tutor', $this->course_id))
    {
        $actions = new ActionsWidget ();
        $upload = '';

        if (!empty($connectedSeries)) {
            $actions->addLink(
                $_("Verknüpfung aufheben"),
                PluginEngine::getLink ('opencast/course/remove_series/' . get_ticket()),
                new Icon('trash', 'clickable')
            );

            $actions->addLink(
                $_("Episodenliste aktualisieren"),
                PluginEngine::getLink ('opencast/course/refresh_episodes/' . get_ticket()),
                new Icon('refresh', 'clickable')
            );

            if ($series_metadata[0]['schedule'] == '1') {
                $actions->addLink($_("Medien hochladen"), '#1',
                    new Icon('upload', 'clickable'), array (
                    'id' => 'oc_upload_dialog'
                ));

                $actions->addLink($_("Workflow konfigurieren"),
                    $controller->url_for('course/workflow'),
                    new Icon('admin', 'clickable'), array(
                        'data-dialog' => 'size=auto'
                ));
            }

        } else {
            $actions->addLink(
                $_('Neue Series anlegen'),
                PluginEngine::getLink ('opencast/course/create_series/'),
                new Icon('tools', 'clickable')
            );

            $actions->addLink(
                $_('Vorhandene Series verknüpfen'), '#',
                new Icon('group', 'clickable'),
                array (
                    'id' => 'oc_config_dialog'
            ));
        }


        //todo - should this already be visibile for teachers?
        if ($coursevis == 'visible'){
            $actions->addLink(
                $_("Reiter verbergen"),
                PluginEngine::getLink ('opencast/course/toggle_tab_visibility/' . get_ticket()),
                new Icon('visibility-visible', 'clickable')
            );
        } else {
            $actions->addLink(
                $_("Reiter sichtbar machen"),
                PluginEngine::getLink ('opencast/course/toggle_tab_visibility/' . get_ticket()),
                new Icon('visibility-invisible', 'clickable')
            );
        }

        $sidebar->addWidget ($actions);
        Helpbar::get()->addPlainText ('', $_("Hier sehen Sie eine Übersicht ihrer Vorlesungsaufzeichnungen. Sie können über den Unterpunkt Aktionen weitere Medien zur Liste der Aufzeichnungen hinzufügen. Je nach Größe der Datei kann es einige Zeit in Anspruch nehmen, bis die entsprechende Aufzeichnung in der Liste sichtbar ist. Weiterhin ist es möglich die ausgewählten Sichtbarkeit einer Aufzeichnung innerhalb der Veranstaltung direkt zu ändern."));
    } else {
        Helpbar::get()->addPlainText ('', $_("Hier sehen Sie eine Übersicht ihrer Vorlesungsaufzeichnungen."));
    }

    Helpbar::get()->addLink('Bei Problemen: '. $GLOBALS['UNI_CONTACT'], 'mailto:'. $GLOBALS['UNI_CONTACT'] .'?subject=[Opencast] Feedback');
?>

<h1>
  <?= $_('Vorlesungsaufzeichnungen') ?>
</h1>

<? if(!(empty($ordered_episode_ids)) || !(empty($states))) : ?>
<div class="oc_flex">
    <div id="episodes" class="oc_flexitem oc_flexepisodelist">
        <span class="oce_episode_search">
            <input class="search" placeholder="<?= $_('Nach Aufzeichnung suchen') ?>" size="30" />
            <?= Icon::create('search', 'clickable', array(
                'class' => 'sort',
                'data-sort' => 'name'
            )) ?>
        </span>
        <ul class="oce_list list"
            <?=($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) ? 'id="oce_sortablelist"' : ''?>>
            <? if($GLOBALS['perm']->have_studip_perm('tutor', $course_id) && !empty($states)) :?>
                <? foreach($states as $workflow_id => $state) :?>
                <li class="uploaded oce_item">
                    <? if(in_array($state->state,array('FAILING','FAILED'))) : ?>
                        <div class="oce_wip">
                            <div class="oce_wip_preview">
                                <img src="<?= $plugin->getPluginURL() .'/images/oc_logo_red.png' ?>">
                            </div>
                        </div>
                        <div class="oce_metadatacontainer oce_failedstate">
                            <h2 class="oce_list_title">
                                <?= htmlready(studip_utf8decode($state->mediapackage->title))?>
                            </h2>

                            <div>
                                <?=$_("Videoverarbeitung fehlgeschlagen")?>
                            </div>

                            <?= Studip\LinkButton::create($_('Daten vom Server entfernen'), PluginEngine::getLink('opencast/course/remove_failed/' . $state->id)); ?></span>
                        </div>
                    <? else :?>
                        <div class="oce_wip" id="<?=$workflow_id?>" >
                            <div class="oce_wip_preview">
                                <img src="<?= $plugin->getPluginURL() .'/images/oc_logo_black.png' ?>">
                            </div>

                            <div style="clear: both;"></div>
                        </div>
                        <div style="margin-left:110px;">
                            <h3 class="oce_list_title"><?=$_('Video wird verarbeitet: ')?> <?= htmlready(studip_utf8decode($state->mediapackage->title))?></h3>
                            <span class="oce_list_date"><?=sprintf($_("Hochgeladen am %s"),date("d.m.Y H:i",strtotime($state->mediapackage->start)))?></span>
                        </div>
                    <? endif; ?>
                </li>
                <? endforeach;?>
            <? endif;?>
            <? foreach($ordered_episode_ids as $pos => $item) : ?>
                <?
                    $image = $item['presentation_preview'];
                    if(empty($image)){
                        $image = ($item['preview'] != false) ? $item['preview'] : $plugin->getPluginURL() . '/images/default-preview.png' ;
                    }
                ?>
                <li id="<?=$item['id']?>"
                class="<?=($item['visibility'] != 'false') ? 'oce_item' : 'hidden_ocvideodiv oce_item'?>"
                data-courseId="<?=$course_id?>"
                data-visibility="<?=$item['visibility']?>"
                data-pos="<?=$pos?>"
                data-mkdate="<?=$item['mkdate']?>"
                data-previewimage="<?=$image?>">
                <div class="oc_flexitem oc_flexplaycontainer">
                    <div id="oc_balls" class="la-ball-scale-ripple-multiple la-3x">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <div class="oce_playercontainer">
            </span>
                        <? $plugin = PluginEngine::getPlugin('OpenCast'); ?>
                        <a href="<?= URLHelper::getURL($video_url.$item['id']) ?>" target="_blank">
                <span class="previewimage">
                    <img class="previewimage" src="<?= $image ?>">
                    <img class="playbutton" style="bottom:10px" src="<?= $plugin->getPluginURL() .'/images/play-circle.png' ?>">
                </span>
                        </a>
                    </div>
                </div>
                <div class="oce_metadatacontainer">
                    <div>
                        <h2 class="oce_metadata oce_list_title">
                            <?= $item['title']?>
                        </h2>
                        <ul class="oce_contetlist">
                            <li class="oce_list_date" >
                                <?= $_('Aufzeichnungsdatum') ?>:
                                <?= date("d.m.Y H:i",strtotime($item['start'])) ?> <?= $_("Uhr") ?>
                            </li>
                            <li>
                                <?= $_('Autor') ?>:
                                <?= $item['author'] ? htmlReady($item['author']) : 'Keine Angaben vorhanden' ?>
                            </li>
                            <li>
                                <?= $_('Beschreibung') ?>:
                                <?= $item['description'] ? htmlReady($item['description']) : 'Keine Beschreibung vorhanden' ?>
                            </li>
                        </ul>
                    </div>

                    <div class="ocplayerlink">
                        <select name="download_quality_select" onchange="OC.change_download_quality($(this).val(),'<?= $item['id'] ?>')">
                            <option selected value="hd"><?= $_('Auflösung: HD') ?></option>
                            <option value="high"><?= $_('Auflösung: Hoch') ?></option>
                            <option value="medium"><?= $_('Auflösung: Mittel') ?></option>
                            <option value="low"><?= $_('Auflösung: Niedrig') ?></option>
                        </select>
                        <div class="button-group">
                            <? echo $download_options[$item['id']]; ?>
                            <? if ($GLOBALS['perm']->get_studip_perm($course_id) == 'autor') : ?>
                                <?= Studip\LinkButton::create($_('Feedback'), 'mailto:' . $GLOBALS['UNI_CONTACT'] . '?subject=[Opencast] Feedback&body=%0D%0A%0D%0A%0D%0ALink zum betroffenen Video:%0D%0A' . PluginEngine::getLink('opencast/course/index/' . $item['id'])); ?>
                            <? endif ?>

                            <? if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) : ?>
                                <? if ($item['visibility'] == 'false') : ?>
                                    <?= Studip\LinkButton::create($_('Aufzeichnung unsichtbar'), PluginEngine::getLink('opencast/course/toggle_visibility/' . $item['id']), ['class' => 'ocinvisible ocspecial', 'id' => 'oc-togglevis', 'data-episode-id' => $item['id']]); ?>
                                <? else : ?>
                                    <?= Studip\LinkButton::create($_('Aufzeichnung sichtbar'), PluginEngine::getLink('opencast/course/toggle_visibility/' . $item['id']), ['class' => 'ocvisible ocspecial', 'id' => 'oc-togglevis', 'data-episode-id' => $item['id']]); ?>
                                <? endif; ?>
                            <? endif; ?>
                        </div>
                    </div>
                </div>
            </li>
            <? endforeach; ?>
        </ul>
    </div>
</div>

<script type="text/javascript">
    $("select[name='download_quality_select']").each(function (index,object) {
        $(object).click();
    });
</script>

<? else: ?>
    <? if(empty($this->connectedSeries) && $GLOBALS['perm']->have_studip_perm('dozent', $course_id)) :?>
            <?= MessageBox::info($_("Sie haben noch keine Series aus Opencast mit dieser Veranstaltung verknüpft. Bitte erstellen Sie eine neue Series oder verknüpfen eine bereits vorhandene Series.")) ?>
    <? else: ?>
        <?=MessageBox::info($_('Es wurden bislang keine Vorlesungsaufzeichnungen bereitgestellt.'));?>
    <? endif;?>
<? endif; ?>


<? if($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) :?>

<div id="upload_dialog" title="<?=$_("Medienupload")?>" style="display: none;">
<?= $this->render_partial("course/_upload", array('course_id' => $course_id, 'dates' => $dates, 'series_id' => $this->connectedSeries[0]['identifier'])) ?>
</div>

<div id="config_dialog" title="<?=$_("Series verknüpfen")?>" style="display: none;">
    <?= $this->render_partial("course/_config", array()) ?>
</div>

<? endif;?>

<!--- hidden -->
<div class="hidden" id="course_id" data-courseId="<?=$course_id?>"></div>
