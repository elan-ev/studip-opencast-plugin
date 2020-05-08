<?
use Opencast\LTI\OpencastLTI;
use Opencast\LTI\LtiLink;
?>

<? if ($flash['delete']) : ?>
    <?= createQuestion2(sprintf(    // question
        $_('Wollen Sie die Verknüpfung zur Series "%s" wirklich aufheben?'),
            $this->connectedSeries[0]['title']
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


<?= $this->render_partial('messages') ?>
<script>
jQuery(function() {
    STUDIP.hasperm = <?= var_export($GLOBALS['perm']->have_studip_perm('tutor', $this->course_id)) ?>;
    OC.states = <?= json_encode($states) ?>;
    OC.initIndexpage();
});
</script>

<?
if ($this->connectedSeries[0]['series_id']) :
    $current_user_id = $GLOBALS['auth']->auth['uid'];

    $lti_link = new LtiLink(
        OpencastLTI::getSearchUrl($this->course_id),
        $config['lti_consumerkey'],
        $config['lti_consumersecret']
    );

    $lti_link->addCustomParameter('tool', '/ltitools');

    if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id, $current_user_id)) {
        $role = 'Instructor';
    } else if ($GLOBALS['perm']->have_studip_perm('autor', $course_id, $current_user_id)) {
        $role = 'Learner';
    }

    $lti_link->setUser($current_user_id, $role);
    $lti_link->setCourse($course_id);
    $lti_link->setResource(
        $this->connectedSeries[0]['series_id'],
        'series',
        'view complete series for course'
    );

    $launch_data = $lti_link->getBasicLaunchData();
    $signature   = $lti_link->getLaunchSignature($launch_data);

    $launch_data['oauth_signature'] = $signature;

    if ($GLOBALS['perm']->have_studip_perm('tutor', $this->course_id)
        && \Config::get()->OPENCAST_ALLOW_STUDIO
    ) {
        $studio_lti_link = new LtiLink(
            $config['service_url'] . '/lti',
            $config['lti_consumerkey'],
            $config['lti_consumersecret']
        );

        $studio_lti_link->addCustomParameter('tool', '/ltitools');

        $studio_lti_link->setUser($current_user_id, 'Instructor');
        $studio_lti_link->setCourse($course_id);
        $studio_lti_link->setResource(
            $this->connectedSeries[0]['series_id'],
            'series'
        );

        $studio_launch_data = $studio_lti_link->getBasicLaunchData();
        $studio_signature   = $studio_lti_link->getLaunchSignature($studio_launch_data);

        $studio_launch_data['oauth_signature'] = $studio_signature;
    }
?>

<script>
OC.ltiCall('<?= $lti_link->getLaunchURL() ?>', <?= json_encode($launch_data) ?>, function() {
    jQuery('img.previewimage').each(function() {
        this.src = this.dataset.src;
    });

<? if ($studio_lti_link && \Config::get()->OPENCAST_ALLOW_STUDIO): ?>
    OC.lti_done = 0;
    OC.ltiCall('<?= $studio_lti_link->getLaunchURL() ?>', <?= json_encode($studio_launch_data) ?>, function() {});
<? endif ?>
});
</script>
<?
/*
?>

<script>
OC.ltiCall('<?= OpencastLTI::getSearchUrl($this->course_id) ?>', <?= json_encode($lti_data) ?>, function() {
    jQuery('img.previewimage').each(function() {
        this.src = this.dataset.src;
    });
});
</script>
<?
*/
endif;

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

        if ($can_schedule) {
            $actions->addLink(
                $_("Medien hochladen"),
                $controller->url_for('course/upload'),
                new Icon('upload', 'clickable'),
                []
            );

            if (\Config::get()->OPENCAST_ALLOW_STUDIO) {
                $actions->addLink($_("Video aufnehmen"),
                    URLHelper::getLink($config['service_url'] . '/studio/index.html', [
                        'cid' => null,
                        'upload.seriesId' => $connectedSeries[0]['series_id']
                    ]),
                    new Icon('video2', 'clickable'), [
                        'target' => '_blank'
                    ]
                );
            }

            // TODO: Schnittool einbinden - Passender Workflow kucken

            if ($perm->have_perm('root')) {
                $actions->addLink($_("Kursspezifischen Workflow konfigurieren"),
                    $controller->url_for('course/workflow'),
                    new Icon('admin', 'clickable'), [
                        'data-dialog' => 'size=auto'
                    ]);
            }
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

        if (Config::get()->OPENCAST_SHOW_TOS && !$perm->have_perm('root')) {
            $actions->addLink(
                $_("Datenschutzeinwilligung zurückziehen"),
                PluginEngine::getLink('opencast/course/withdraw_tos/' . get_ticket()),
                new Icon('decline', 'clickable')
            );
        }

        if ($perm->have_perm('root')) {
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

        if ($controller->isDownloadAllowed()) {
            $actions->addLink(
                $_("Downloads verhindern"),
                PluginEngine::getLink('opencast/course/disallow_download/' . get_ticket()),
                new Icon('download+decline', 'clickable')
            );
        } else {
            $actions->addLink(
                $_("Downloads erlauben"),
                PluginEngine::getLink('opencast/course/allow_download/' . get_ticket()),
                new Icon('download+accept', 'clickable')
            );
        }

    } else {
        $actions->addLink(
            $_('Neue Series anlegen'),
            PluginEngine::getLink('opencast/course/create_series/'),
            new Icon('tools', 'clickable')
        );

        if ($perm->have_perm('root')) {
            $actions->addLink(
                $_('Vorhandene Series verknüpfen'), PluginEngine::getLink('opencast/course/config/'),
                new Icon('group', 'clickable'),
                [
                    'data-dialog' => 'width=550;height=500'
                ]);
        }
    }

    $sidebar->addWidget($actions);
    Helpbar::get()->addPlainText('', $_("Hier sehen Sie eine Übersicht ihrer Vorlesungsaufzeichnungen. Sie können über den Unterpunkt Aktionen weitere Medien zur Liste der Aufzeichnungen hinzufügen. Je nach Größe der Datei kann es einige Zeit in Anspruch nehmen, bis die entsprechende Aufzeichnung in der Liste sichtbar ist. Weiterhin ist es möglich die ausgewählten Sichtbarkeit einer Aufzeichnung innerhalb der Veranstaltung direkt zu ändern."));
} else {
    Helpbar::get()->addPlainText('', $_("Hier sehen Sie eine Übersicht ihrer Vorlesungsaufzeichnungen."));
}

Helpbar::get()->addLink('Bei Problemen: ' . $GLOBALS['UNI_CONTACT'], 'mailto:' . $GLOBALS['UNI_CONTACT'] . '?subject=[Opencast] Feedback');
?>

<? if (!(empty($ordered_episode_ids)) || !(empty($wip_episodes))) : ?>
    <? if ($perm->have_studip_perm('tutor', Context::getId())) : ?>
        <?= $this->render_partial('course/_wip_episode') ?>
    <? endif ?>

    <? if (!(empty($ordered_episode_ids))) : ?>
        <?= $this->render_partial('course/_episode') ?>
    <? endif ?>
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
                <span>
                    <?= $_("Unsichtbar - Für Lehrende und Tutor/-innen dieser Veranstaltung sichtbar") ?>
                </span>
            </label>

            <label>
                <input type="radio" name="visibility" value="visible">
                <span>
                    <?= $_("Sichtbar - Für Teilnehmende dieser Veranstaltung sichtbar") ?>
                </span>
            </label>

            <? if ($multiconnected) : ?>
                <label class="oc_muted">
                    <input type="radio" name="visibility" value="visible" disabled="disabled" style="float: left">
                    <span>
                        <?= $_("Diese Videoserie ist mit mehreren Seminaren verknüpft, das Video kann daher nicht freigegeben werden.") ?>
                    </span>
                </label>
            <? else : ?>
                <label>
                    <input type="radio" name="visibility" value="free">
                    <span>
                        <?= $_("Freigeben - Dieses Video ist für jeden sichtbar") ?>
                    </span>
                </label>
            <? endif ?>
        </fieldset>

        <footer data-dialog-button>
            <?= Studip\Button::createAccept(_('Speichern'), ['onclick' => "OC.setVisibility(jQuery('#visibility_dialog input[name=visibility]:checked').val(), jQuery('#visibility_dialog').attr('data-episode_id'));return false;"]) ?>
            <?= Studip\Button::createCancel(_('Abbrechen'), ['onclick' => "jQuery('#visibility_dialog').dialog('close');return false;"]) ?>
        </footer>
    </form>
</div>
