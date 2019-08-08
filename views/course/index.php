<? if ($flash['delete']) : ?>
    <?= createQuestion2(sprintf(    // question
        $_('Wollen Sie die Verknüpfung zur Series "%s" wirklich aufheben?'),
            utf8_decode($this->connectedSeries[0]['title'])
        ),
        [   // approveParams
            'course_id' => $course_id,
            'series_id' => $this->connectedSeries[0]['series_id'],
            'delete' => true
        ],
        [   // disapproveParams
            'cancel' => true
        ],
        PluginEngine::getLink('opencast/course/remove_series/' . get_ticket())  // baseUrl
    ) ?>
<? endif ?>

<?
$visibility_text = [
    'invisible' => 'Video ist nur für Sie sichtbar',
    'visible'   => 'Video ist für Teilnehmende sichtbar',
    'free'      => 'Video ist für jeden sichtbar'
];
?>

<?= $this->render_partial('messages') ?>

<script>
jQuery(function() {
    STUDIP.hasperm = <?= var_export($GLOBALS['perm']->have_studip_perm('tutor', $this->course_id)) ?>;
    OC.states = <?= json_encode($states) ?>;
    OC.visibility_text = <?= json_encode($visibility_text) ?>;
    OC.initIndexpage();
});
</script>

<?
$current_user_id = $GLOBALS['auth']->auth['uid'];
$lti_launch_data = OpencastLTI::generate_lti_launch_data(
    $current_user_id,
    $course_id,
    LTIResourceLink::generate_link('series','view complete series for course'),
    OpencastLTI::generate_tool('series', $this->connectedSeries[0]['series_id'])
);

$lti_data = OpencastLTI::sign_lti_data($lti_launch_data, $config['lti_consumerkey'], $config['lti_consumersecret']);
?>

<script>
    // send credentials to opencast lti backend, setting session cookie for oc domain
    $.ajax({
        type: "POST",
        url: "<?= rtrim($config['service_url'], '/') ?>/lti",
        data:  <?= json_encode($lti_data) ?>,
        xhrFields: {
           withCredentials: true
        },
        crossDomain: true
    });
</script>
<?
global $perm;
$sidebar = Sidebar::get();

if ($GLOBALS['perm']->have_studip_perm('tutor', $this->course_id)) {
    $actions = new ActionsWidget ();
    $upload = '';

    if (!empty($connectedSeries)) {
        $actions->addLink(
            $_("Verknüpfung aufheben"),
            PluginEngine::getLink('opencast/course/remove_series/' . get_ticket()),
            new Icon('trash', 'clickable')
        );

        $actions->addLink(
            $_("Episodenliste aktualisieren"),
            PluginEngine::getLink('opencast/course/refresh_episodes/' . get_ticket()),
            new Icon('refresh', 'clickable')
        );

        if ($can_schedule) {
            $actions->addLink($_("Medien hochladen"),
                $controller->url_for('course/upload'),
                new Icon('upload', 'clickable'), [
                    'data-dialog' => 'size=auto'
                ]);
            if ($perm->have_perm('root')) {
                $actions->addLink($_("Kursspezifischen Workflow konfigurieren"),
                    $controller->url_for('course/workflow'),
                    new Icon('admin', 'clickable'), [
                        'data-dialog' => 'size=auto'
                    ]);
            }
        }

    } else {
        $actions->addLink(
            $_('Neue Series anlegen'),
            PluginEngine::getLink('opencast/course/create_series/'),
            new Icon('tools', 'clickable')
        );

        $actions->addLink(
            $_('Vorhandene Series verknüpfen'), PluginEngine::getLink('opencast/course/config/'),
            new Icon('group', 'clickable'),
            [
                'data-dialog' => 'width=400;height=500'
            ]);
    }

    if ($coursevis == 'visible') {
        $actions->addLink(
            $_("Reiter verbergen"),
            PluginEngine::getLink('opencast/course/toggle_tab_visibility/' . get_ticket()),
            new Icon('visibility-visible', 'clickable')
        );
    } else {
        $actions->addLink(
            $_("Reiter sichtbar machen"),
            PluginEngine::getLink('opencast/course/toggle_tab_visibility/' . get_ticket()),
            new Icon('visibility-invisible', 'clickable')
        );
    }

    if ($GLOBALS['perm']->have_perm('root')) {
        $actions->addLink(
            $can_schedule
                ? $_("Medienaufzeichnung verbieten")
                : $_("Medienaufzeichnung erlauben"),
            PluginEngine::getLink('opencast/course/toggle_schedule/' . get_ticket()),
            new Icon($can_schedule
                ? 'video'
                : 'video+decline', 'clickable')
        );
    }

    $sidebar->addWidget($actions);
    Helpbar::get()->addPlainText('', $_("Hier sehen Sie eine Übersicht ihrer Vorlesungsaufzeichnungen. Sie können über den Unterpunkt Aktionen weitere Medien zur Liste der Aufzeichnungen hinzufügen. Je nach Größe der Datei kann es einige Zeit in Anspruch nehmen, bis die entsprechende Aufzeichnung in der Liste sichtbar ist. Weiterhin ist es möglich die ausgewählten Sichtbarkeit einer Aufzeichnung innerhalb der Veranstaltung direkt zu ändern."));
} else {
    Helpbar::get()->addPlainText('', $_("Hier sehen Sie eine Übersicht ihrer Vorlesungsaufzeichnungen."));
}

Helpbar::get()->addLink('Bei Problemen: ' . $GLOBALS['UNI_CONTACT'], 'mailto:' . $GLOBALS['UNI_CONTACT'] . '?subject=[Opencast] Feedback');
?>

<h1>
    <?= $_('Vorlesungsaufzeichnungen') ?>
</h1>

<?
if (!(empty($ordered_episode_ids)) || !(empty($states))) : ?>
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
                                        <?= htmlready(studip_utf8decode($state->mediapackage->title)) ?>
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
                                    <h3 class="oce_list_title"><?= $_('Video wird verarbeitet: ') ?> <?= htmlready(studip_utf8decode($state->mediapackage->title)) ?></h3>
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
                                        src="<?= $image ?>"
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
                                    <?= $this->render_partial("course/_download", ['course_id' => $course_id, 'series_id' => $this->connectedSeries[0]['identifier'], 'episode'=> $item]) ?>
                                </div>
                                <div class="button-group">
                                    <? echo $download_options[$item['id']]; ?>
                                    <? if ($GLOBALS['perm']->get_studip_perm($course_id) == 'autor') : ?>
                                        <?= Studip\LinkButton::create($_('Feedback'), 'mailto:' . $GLOBALS['UNI_CONTACT'] . '?subject=[Opencast] Feedback&body=%0D%0A%0D%0A%0D%0ALink zum betroffenen Video:%0D%0A' . PluginEngine::getLink('opencast/course/index/' . $item['id'])); ?>
                                    <? endif ?>

                                    <? if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) : ?>
                                            <?= Studip\LinkButton::create($_($visibility_text[$item['visibility']]),
                                                '', [
                                                'class'           => 'oc-togglevis ocspecial oc'. $item['visibility'],
                                                'data-episode-id' => $item['id'],
                                                'data-dialog'     => 'size=auto',
                                                'data-visibility' => $item['visibility']
                                            ]); ?>
                                    <? endif; ?>
                                </div>
                            </div>

                    </li>
                <? endforeach; ?>
            </ul>
        </div>
    </div>

<? else: ?>
    <? if (empty($this->connectedSeries) && $GLOBALS['perm']->have_studip_perm('dozent', $course_id)) : ?>
        <? if ($this->config_error) : ?>
            <?= MessageBox::error($_('Für aktuell verknüpfte Serie ist eine fehlerhafte Konfiguration hinterlegt!')) ?>
        <? else : ?>
            <?= MessageBox::info($_("Sie haben noch keine Series aus Opencast mit dieser Veranstaltung verknüpft. Bitte erstellen Sie eine neue Series oder verknüpfen eine bereits vorhandene Series.")) ?>
        <? endif; ?>
    <? else: ?>
        <?= MessageBox::info($_('Es wurden bislang keine Vorlesungsaufzeichnungen bereitgestellt.')); ?>
    <? endif; ?>
<? endif; ?>

<!--- hidden -->
<div class="hidden" id="course_id" data-courseId="<?= $course_id ?>"></div>
<div id="visibility_dialog" style="display: none">
    <form class="default" method="post">
        <fieldset>
            <legend>Sichtbarkeit einstellen</legend>

            <label>
                <input type="radio" name="visibility" value="invisible">
                Unsichtbar - Dieses Video ist nur für Sie sichtbar
            </label>

            <label>
                <input type="radio" name="visibility" value="visible">
                Sichtbar - Dieses Video ist für Teilnehmende dieser Veranstaltung sichtbar
            </label>

            <label>
                <input type="radio" name="visibility" value="free">
                Freigeben - Dieses Video ist für jeden sichtbar
            </label>
        </fieldset>

        <footer data-dialog-button>
            <?= Studip\Button::createAccept(_('Speichern'), ['onclick' => "OC.setVisibility(jQuery('#visibility_dialog input[name=visibility]:checked').val(), jQuery('#visibility_dialog').attr('data-episode_id'));return false;"]) ?>
            <?= Studip\Button::createCancel(_('Abbrechen'), ['onclick' => "jQuery('#visibility_dialog').dialog('close');return false;"]) ?>
        </footer>
    </form>
</div>
