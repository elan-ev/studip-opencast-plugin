<?
use Opencast\LTI\OpencastLTI;
use Opencast\LTI\LTIResourceLink;
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
$lti_launch_data = OpencastLTI::generate_lti_launch_data(
    $current_user_id,
    $course_id,
    LTIResourceLink::generate_link('series','view complete series for course'),
    OpencastLTI::generate_tool('series', $this->connectedSeries[0]['series_id'])
);

$lti_data = OpencastLTI::sign_lti_data(
    $lti_launch_data,
    $config['lti_consumerkey'],
    $config['lti_consumersecret'],
    OpencastLTI::getSearchUrl($this->course_id)
);
?>

<script>
OC.ltiCall('<?= OpencastLTI::getSearchUrl($this->course_id) ?>', <?= json_encode($lti_data) ?>, function() {
    jQuery('img.previewimage').each(function() {
        this.src = this.dataset.src;
    });
});
</script>
<?
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

<h1>
    <?= $_('Vorlesungsaufzeichnungen') ?>
</h1>

<? if (!(empty($ordered_episode_ids)) || !(empty($states))) : ?>
    <?= $this->render_partial('course/_episode') ?>
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
