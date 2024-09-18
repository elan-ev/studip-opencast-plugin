<?php
use Studip\Button;
use Opencast\Models\Pager;
?>

<?
$visibility_text = [
    'invisible' => $_('Video ist nur für Sie sichtbar'),
    'visible'   => $_('Video ist für Teilnehmende sichtbar'),
    'free'      => $_('Video ist für jeden sichtbar')
];

$sort_orders = Pager::getSortOptions();
?>

<form action="<?= $controller->url_for('course/sort_order') ?>" method=post>
    <select name="order">
        <?
        $sort_str = 'mkdate1';
        if ($_SESSION['opencast']['sort_order']) {
            $sort_str = $_SESSION['opencast']['sort_order'];
        }
        else if (CourseConfig::get($course_id)->COURSE_SORT_ORDER) {
            $sort_str = CourseConfig::get($course_id)->COURSE_SORT_ORDER;
        }
        ?>
        <? foreach ($sort_orders as $key => $sort_order) : ?>
            <? if ($sort_str === $key) : ?>
                <option selected value="<?= $key ?>"><?= $sort_order ?></option>
            <? else : ?>
                <option value="<?= $key ?>"><?= $sort_order ?></option>
            <? endif ?>
        <? endforeach; ?>
    </select>

    <?= Button::createAccept($_('Übernehmen'), ['title' => $_('Änderungen übernehmen')]); ?>

</form>

<? if (OCPerm::editAllowed($course_id) && !empty($eventsInProcessing)) : ?>
    <?= $this->render_partial('course/_wip_episode') ?>
<? endif ?>


<?= $pagechooser = $GLOBALS['template_factory']->render('shared/pagechooser', [
    'page'         => Pager::getPage(),
    'num_postings' => Pager::getLength(),
    'perPage'      => Pager::getLimit(),
    'pagelink'     => PluginEngine::getURL('opencast/course/index/?search='. Pager::getSearch() .'&page=') . '%s'
]); ?>

<script type="text/javascript">
    OC.visibility_text = <?= json_encode($visibility_text) ?>;
</script>

<div class="oc_flex">
    <div id="episodes" class="oc_flexitem oc_flexepisodelist">
        <ul class="oce_list list" <?= (OCPerm::editAllowed($course_id)) ? 'id="oce_sortablelist"' : '' ?>>
            <? foreach ($ordered_episode_ids as $pos => $item) : ?>
                <?
                $now = time();
                $startTime = strtotime($item['start']);
                $endTime = strtotime($item['start']) + $item['duration'] / 1000;
                ?>

                <li id="<?= $item['id'] ?>"
                    class="<?= (!empty($item['visibility']) && $item['visibility'] != 'false') ? 'oce_item' : 'hidden_ocvideodiv oce_item' ?>"
                    data-courseId="<?= $course_id ?>"
                    data-visibility="<?= $item['visibility'] ?? '' ?>"
                    data-pos="<?= $pos ?>"
                    data-mkdate="<?= $item['mkdate'] ?>"
                >
                    <div class="oc_flexitem oc_flexplaycontainer">
                        <div id="oc_balls" class="la-ball-scale-ripple-multiple la-3x">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                        <div class="oce_playercontainer">
                            <? $plugin = PluginEngine::getPlugin('OpenCast'); ?>
                            <? if ($item['is_retracting']) : ?>
                                <span class="previewimage">
                                    <img class="previewimage <?= (!empty($item['visibility']) && $item['visibility'] == 'false') ? 'ocinvisible' : '' ?>"
                                         src="<?= $controller->url_for('redirect/preview/'.  $item['id']) ?>" height="200"
                                         style="filter: grayscale(100%);">
                                    </img>
                                </span>
                            <? else: ?>
                                <a href="<?=str_replace('%%%video_id%%%', $item['id'], $video_url) ?>" target="_blank">
                                    <span class="previewimage">
                                        <img class="previewimage <?= (!empty($item['visibility']) && $item['visibility'] == 'false') ? 'ocinvisible' : '' ?>"
                                             src="<?= $controller->url_for('redirect/preview/'.  $item['id']) ?>" height="200">
                                        </img>

                                        <img class="playbutton"
                                            src="<?= $plugin->getPluginURL() . '/images/play.svg' ?>">
                                    </span>
                                </a>
                            <? endif ?>
                        </div>
                    </div>
                    <div class="oce_metadatacontainer">
                        <div>
                            <h2 class="oce_metadata oce_list_title">
                                <? if ($item['visibility'] == 'free') : ?>
                                    <a href="<?= str_replace('%%%video_id%%%', $item['id'], $video_url) ?>" target="_blank">
                                        <?= Icon::create('group', 'clickable', [
                                            'style' => 'vertical-align: middle; margin-right: 3px;',
                                            'title' => 'Direktlink, Rechtskick -> Link-Adresse kopieren'
                                        ]) ?>
                                    </a>
                                <? endif ?>
                                <?= htmlReady($item['title']) ?>
                            </h2>
                            <ul class="oce_contentlist">
                                <li class="oce_list_date">
                                    <?= $_('Aufnahmezeitpunkt') ?>:
                                    <?= date("d.m.Y H:i", strtotime($item['start'])) ?> <?= $_("Uhr") ?>
                                </li>
                                <li>
                                    <?= $_('Vortragende:') ?>
                                    <?= $item['author'] ? htmlReady($item['author']) : 'Keine Angaben vorhanden' ?>
                                </li>
                                <li>
                                    <?= $_('Spieldauer:') ?>
                                    <?= $item['duration'] ? htmlReady(
                                           gmdate("H:i:s", $item['duration']/1000)
                                      ) : 'Spieldauer wurde nicht ermittelt' ?>
                                </li>
                                <li>
                                    <?= $_('Beschreibung:') ?>
                                    <?= $item['description'] ? htmlReady($item['description']) : 'Keine Beschreibung vorhanden' ?>
                                </li>
                            </ul>
                        </div>

                        <? if (!$item['is_retracting']) : ?>
                            <div class="ocplayerlink">
                                <? $base = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']); ?>
                                <?= Studip\LinkButton::create(
                                        $_('Technisches Feedback'),
                                        'mailto:' . Config::get()->OPENCAST_SUPPORT_EMAIL
                                            . '?subject=[Opencast] Feedback&body=%0D%0A%0D%0A%0D%0ALinks zum betroffenen Video:%0D%0A'
                                            . URLHelper::getLink($controller->link_for('course/index/' . $item['id'])) . "%0D%0A"
                                            . str_replace('%%%video_id%%%', $item['id'], $video_url),
                                        [
                                            'target' => '_blank',
                                            'rel' => 'noopener noreferrer',
                                            'class' => 'oc_feedback',
                                            'title' => $_('Technisches Feedback geben (Ton- oder Abspielprobleme etc.)'),
                                        ]
                                ); ?>
                                <? URLHelper::setBaseURL($base); ?>

                                <? if (OCPerm::editAllowed($course_id)) : ?>
                                    <?= Studip\LinkButton::create($_($visibility_text[$item['visibility']] ?? $_('Unbekannte Sichtbarkeit')),
                                        '', [
                                            'class'           => 'oc-togglevis ocspecial oc' . ($item['visibility'] ?? 'free'),
                                            'data-episode-id' => $item['id'],
                                            'data-visibility' => $item['visibility'] ?? 'invisible',
                                            'title'           => $_('Sichtbarkeit für dieses Video ändern')
                                        ]); ?>

                                    <? if ($item['has_previews']) : ?>

                                        <?= Studip\LinkButton::create(
                                            $_('Schnitteditor öffnen'),
                                                $controller->url_for('redirect/perform/editor/'. $item['id']),
                                            [
                                                'target' => '_blank',
                                                'class'  => 'oc_editor',
                                                'title'  => $_('Schnitteditor öffnen')
                                            ]
                                        ); ?>

                                        <? if ($item['annotation_tool']) : ?>
                                            <?= Studip\LinkButton::create(
                                                $_('Anmerkungen hinzufügen'),
                                                $controller->url_for('redirect/perform/annotation/'. $item['id']),
                                                [
                                                    'target' => '_blank',
                                                    'class'  => 'oc_editor',
                                                    'title'  => $_('Anmerkungen hinzufügen')
                                                ]
                                            ); ?>
                                        <? endif ?>
                                    <? endif ?>
                                <? endif; ?>


                                <? if ($controller->isDownloadAllowed()) : ?>
                                    <? if (!empty($item['presenter_download'])
                                        || !empty($item['presentation_download'])
                                        || !empty($item['audio_download'])
                                    ) : ?>
                                        <?= \Studip\LinkButton::create(
                                            $_('Mediendownload'),
                                            '#',
                                            [
                                                'class'           => 'oc_download_dialog',
                                                'data-episode_id' => $item['id'],
                                                'title'           => $_('Mediendownload')
                                            ]
                                        ); ?>
                                    <? endif ?>
                                    <div id="download_dialog-<?= $item['id'] ?>" title="<?= $_("Mediendownload") ?>"
                                         style="display: none;">
                                        <?= $this->render_partial("course/_download", ['course_id' => $course_id, 'series_id' => $this->connectedSeries[0]['series_id'], 'episode' => $item]) ?>
                                    </div>
                                <? elseif (OCPerm::editAllowed($course_id)) : ?>
                                    <? if (!empty($item['presenter_download'])
                                        || !empty($item['presentation_download'])
                                        || !empty($item['audio_download'])
                                    ) : ?>
                                        <?= \Studip\LinkButton::create(
                                            $_('Mediendownload (nur für Lehrende)'),
                                            '#',
                                            [
                                                'class'           => 'oc_download_dialog',
                                                'data-episode_id' => $item['id'],
                                                'title'           => $_('Mediendownload (nur für Lehrende)')
                                            ]
                                        ); ?>
                                    <? endif ?>
                                    <div id="download_dialog-<?= $item['id'] ?>" title="<?= $_("Mediendownload") ?>"
                                         style="display: none;">
                                        <?= $this->render_partial("course/_download", ['course_id' => $course_id, 'series_id' => $this->connectedSeries[0]['series_id'], 'episode' => $item]) ?>
                                    </div>
                                <? endif ?>

                                <? if (OCPerm::editAllowed($course_id)) : ?>
                                    <?= Studip\LinkButton::create(
                                        $_('Metadaten neu laden'),
                                        $controller->url_for('course/refresh_episode/' . get_ticket() . '/' . $item['id']),
                                        [
                                            'class'   => 'oc_refresh',
                                            'title'   => $_('Metadaten von Opencast aktualisieren')
                                        ]
                                    ); ?>

                                    <?= Studip\LinkButton::create(
                                        $_('Entfernen'),
                                        $controller->url_for('course/remove_episode/' . get_ticket() . '/' . $item['id']),
                                        [
                                            'onClick' => "return OC.askForConfirmation('" . $_('Sind sie sicher, dass sie dieses Video löschen möchten?') . "')",
                                            'class'   => 'oc_delete',
                                            'title'   => $_('Dieses Video löschen')
                                        ]
                                    ); ?>
                                <? endif ?>
                            </div>
                        <? else : ?>
                            <div class="ocplayerlink" style="margin-top: 1em;">
                                <?= MessageBox::info($_('Die Aufzeichnung wird gerade gelöscht.')) ?>
                            </div>
                        <? endif ?>
                    </div>
                </li>
            <? endforeach; ?>
        </ul>
    </div>
</div>

<!-- Seitenwähler (bei Bedarf) am unteren Rand anzeigen -->
<?= $pagechooser ?>
