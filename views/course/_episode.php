<?
$visibility_text = [
    'invisible' => 'Video ist nur für Sie sichtbar',
    'visible'   => 'Video ist für Teilnehmende sichtbar',
    'free'      => 'Video ist für jeden sichtbar'
];
?>

<script type="text/javascript">
    OC.visibility_text = <?= json_encode($visibility_text) ?>;
</script>

<div class="oc_flex">
    <div id="episodes" class="oc_flexitem oc_flexepisodelist">
    <span class="oce_episode_search">
        <input class="search" placeholder="<?= $_('Nach Aufzeichnung suchen') ?>" size="30"/>
        <?= Icon::create('search', 'clickable', [
            'class'     => 'sort',
            'data-sort' => 'name'
        ]) ?>
    </span>
        <ul class="oce_list list"
            <?= ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) ? 'id="oce_sortablelist"' : '' ?>>
            <? if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id) && !empty($states)) : ?>
                <? foreach ($states as $workflow_id => $state) : ?>
                    <li class="uploaded oce_item">
                        <? if (in_array($state->state, ['FAILING', 'FAILED'])) : ?>
                            <div class="oce_wip">
                                <div class="oce_wip_preview">
                                    <img src="<?= $plugin->getPluginURL() . '/images/opencast-red.svg' ?>">
                                </div>
                            </div>
                            <div class="oce_metadatacontainer oce_failedstate">
                                <h2 class="oce_list_title">
                                    <?= htmlready($state->mediapackage->title) ?>
                                </h2>

                                <div>
                                    <?= $_("Videoverarbeitung fehlgeschlagen") ?>
                                </div>

                                <?= Studip\LinkButton::create($_('Daten vom Server entfernen'), PluginEngine::getLink('opencast/course/remove_failed/' . $state->id)); ?>
                            </div>
                        <? else : ?>
                            <div class="oce_wip" id="<?= $workflow_id ?>">
                                <div class="oce_wip_preview">
                                    <img src="<?= $plugin->getPluginURL() . '/images/opencast-black.svg' ?>">
                                </div>

                                <div style="clear: both;"></div>
                            </div>
                            <div style="margin-left:110px;">
                                <h3 class="oce_list_title"><?= $_('Video wird verarbeitet: ') ?>
                                <?= htmlready($state->mediapackage->title) ?></h3>
                                <span class="oce_list_date"><?= sprintf($_("Hochgeladen am %s"), date("d.m.Y H:i", strtotime($state->mediapackage->start))) ?></span>
                            </div>
                        <? endif; ?>
                    </li>
                <? endforeach; ?>
            <? endif; ?>
            <? foreach ($ordered_episode_ids as $pos => $item) : ?>
                <?
                $image = $item['presentation_preview'];
                if (empty($image)) {
                    $image = ($item['preview'] != false) ? $item['preview'] : $plugin->getPluginURL() . '/images/default-preview.png';
                }
                ?>
                <li id="<?= $item['id'] ?>"
                    class="<?= ($item['visibility'] != 'false') ? 'oce_item' : 'hidden_ocvideodiv oce_item' ?>"
                    data-courseId="<?= $course_id ?>"
                    data-visibility="<?= $item['visibility'] ?>"
                    data-pos="<?= $pos ?>"
                    data-mkdate="<?= $item['mkdate'] ?>"
                    data-previewimage="<?= $image ?>">
                    <div class="oc_flexitem oc_flexplaycontainer">
                        <div id="oc_balls" class="la-ball-scale-ripple-multiple la-3x">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                        <div class="oce_playercontainer">
                            <? $plugin = PluginEngine::getPlugin('OpenCast'); ?>
                            <a href="<?= URLHelper::getURL($video_url . $item['id']) ?>" target="_blank">
                            <span class="previewimage">
                                <img
                                    class="previewimage <?= $item['visibility'] == 'false' ? 'ocinvisible' : '' ?>"
                                    data-src="<?= $image ?>" height="200"
                                >
                                <img class="playbutton"
                                     src="<?= $plugin->getPluginURL() . '/images/play.svg' ?>">
                            </span>
                            </a>
                        </div>
                    </div>
                    <div class="oce_metadatacontainer">
                        <div>
                            <h2 class="oce_metadata oce_list_title">
                                <? if ($item['visibility'] == 'free') : ?>
                                <a href="<?= URLHelper::getURL($video_url . $item['id']) ?>" target="_blank" >
                                    <?= Icon::create('group', 'clickable', [
                                        'style' => 'vertical-align: middle; margin-right: 3px;',
                                        'title' => 'Direktlink, Rechtskick -> Link-Adresse kopieren'
                                    ]) ?>
                                </a>
                                <? endif ?>
                                <?= $item['title'] ?>
                            </h2>
                            <ul class="oce_contetlist">
                                <li class="oce_list_date">
                                    <?= $_('Aufzeichnungsdatum') ?>:
                                    <?= date("d.m.Y H:i", strtotime($item['start'])) ?> <?= $_("Uhr") ?>
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
                    </div>

                        <div class="ocplayerlink">
                            <?if(!empty($item['presenter_download']) || !empty($item['presentation_download']) || !empty($item['audio_download'])){
                                echo \Studip\LinkButton::create('Mediendownload','#',['class'=>'oc_download_dialog','data-episode_id'=>$item['id']]);
                            } ?>
                            <div id="download_dialog-<?= $item['id']?>" title="<?= $_("Mediendownload") ?>" style="display: none;">
                                <?= $this->render_partial("course/_download", ['course_id' => $course_id, 'series_id' => $this->connectedSeries[0]['series_id'], 'episode'=> $item]) ?>
                            </div>
                            <div class="button-group">
                                <? echo $download_options[$item['id']]; ?>
                                <? if ($GLOBALS['perm']->get_studip_perm($course_id) == 'autor') : ?>
                                    <?= Studip\LinkButton::create($_('Feedback'), 'mailto:' . $GLOBALS['UNI_CONTACT'] . '?subject=[Opencast] Feedback&body=%0D%0A%0D%0A%0D%0ALink zum betroffenen Video:%0D%0A' . PluginEngine::getLink('opencast/course/index/' . $item['id'])); ?>
                                <? endif ?>

                                <? if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) : ?>
                                        <?= Studip\LinkButton::create($_($visibility_text[$item['visibility']] ?: 'Unbekannte Sichtbarkeit'),
                                            '', [
                                            'class'           => 'oc-togglevis ocspecial oc'. ($item['visibility'] ?: 'free'),
                                            'data-episode-id' => $item['id'],
                                            'data-dialog'     => 'size=auto',
                                            'data-visibility' => $item['visibility'] ?: 'invisible'
                                        ]); ?>
                                <? endif; ?>
                            </div>
                        </div>

                </li>
            <? endforeach; ?>
        </ul>
    </div>
</div>
