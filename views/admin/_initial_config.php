<? use Studip\Button,
    Studip\LinkButton,
    Opencast\Configuration,
    Opencast\Constants,
    Opencast\LTI\LtiLink;

    $add_server = true;
?>
<form class="default" action="<?= $controller->url_for('admin/update/') ?>" method=post>

    <? if (OCPerm::editAllowed($course_id) && !$controller->isUploadStudygroupActivatable()) : ?>
        <?= MessageBox::error($_('Das Hochladen durch Studierende ist momentan nicht möglich. Um das Problem zu beheben, muss das Inhaltselement für Opencast aktiv oder wählbar geschaltet werden.')); ?>
    <? endif ?>

    <fieldset class="collapsable">
        <legend><?= $_('Globale Einstellungen'); ?></legend>

            <? foreach (Configuration::getGlobalConfig() as $data) : ?>
                <?= $this->render_partial('admin/_config_' . $data['type'], [
                    'config'    => $data,
                    'config_id' => 'global'
                ]); ?>
            <? endforeach ?>

            <? if (Config::get()->OPENCAST_SHOW_TOS) : ?>
            <label>
                <?= $_('Terms of service') ?>
                <?= I18N::textarea('tos', new I18NString(\Config::get()->OPENCAST_TOS, null, [
                        'object_id' => 1,
                        'table' => 'oc_config',
                        'field' => 'tos'
                    ]), ['class' => 'add_toolbar wysiwyg']) ?>
            </label>
            <? endif ?>

            <label>
                <?= $_('Infotext auf der Hochladeseite (Überschrift)') ?>
                <?= I18N::textarea('upload_info_heading', new I18NString(\Config::get()->OPENCAST_UPLOAD_INFO_TEXT_HEADING, null, [
                        'object_id' => 2,
                        'field' => 'upload_info_heading'
                    ]), ['class' => 'add_toolbar wysiwyg']) ?>
            </label>

            <label>
                <?= $_('Infotext auf der Hochladeseite') ?>
                <?= I18N::textarea('upload_info_body', new I18NString(\Config::get()->OPENCAST_UPLOAD_INFO_TEXT_BODY, null, [
                        'object_id' => 3,
                        'field' => 'upload_info_body'
                    ]), ['class' => 'add_toolbar wysiwyg']) ?>
            </label>

            <label>
                <?= $_('In der Datenbank verwendete Konfigurationen (IDs): ') ?>
                <?
                $counter = 0;
                foreach (Configuration::overall_used_config_ids() as $id=>$tables) :
                    echo ($counter==0?'':', ').'<b title="'.implode(', ',$tables).'">'.$id.'</b>';
                    $counter++;
                endforeach;
                ?>
            </label>
    </fieldset>

    <? foreach ($config as $config_data): ?>
        <? $config_id = $config_data['id'] ?>
        <? if (empty($config_data['service_url'])) $add_server = false ?>
    <fieldset class="collapsable <?= empty($config_data['service_url']) ? 'oc_warning' : '' ?>">
        <legend>
            <?= $_('Opencast Server Einstellungen')." (ID: $config_id) - "
                . $_('OC Version') . ": ". htmlReady($config[$config_id]['service_version']) .".x" ?>
            <span class="oc_form_icon">
                <a href="<?= $controller->url_for('admin/delete_server/' . $config_id) ?>">
                    <?= Icon::create('trash', Icon::ROLE_CLICKABLE, ['title' => $_('Server löschen')]) ?>
                </a>
            </span>
        </legend>

        <span id="oc_lti_success_<?= $config_id ?>" style="display: none;">
        <?= MessageBox::success($_('LTI Konfiguration dieses Servers funktioniert.')) ?>
        </span>

        <span id="oc_lti_error_<?= $config_id ?>" style="display: none;">
        <?= MessageBox::error($_('LTI Konfiguration für diesen Server fehlerhaft!')) ?>
        </span>

        <? foreach (Constants::$DEFAULT_CONFIG as $data) {
            if ($data['name'] == 'livestream') continue; # this option is currently not save to be used
            $instance_config = Configuration::instance($config_id);
            $data['value'] = $instance_config[$data['name']];
        ?>
                <?= $this->render_partial('admin/_config_' . $data['type'], [
                    'config'    => $data,
                    'config_id' => $config_id
                ]); ?>

        <? } ?>

        <?
        if ($instance_config['service_url']
            && $instance_config['lti_consumerkey']
            && $instance_config['lti_consumersecret']
        ) :
            $instance_config = Configuration::instance($config_id);
            $url = parse_url($config[$config_id]['service_url']);
            $lti_link = new LtiLink(
                $url['scheme'] . '://' . $url['host']
                . ($url['port'] ? ':' . $url['port'] : '') . '/lti',
                $instance_config['lti_consumerkey'],
                $instance_config['lti_consumersecret']
            );
            $launch_data = $lti_link->getBasicLaunchData();
            $signature   = $lti_link->getLaunchSignature($launch_data);

            $launch_data['oauth_signature'] = $signature;

            $lti_data = json_encode($launch_data);
            $lti_url  = $lti_link->getLaunchURL();
        ?>
        <script>
        OC.ltiCall('<?= $lti_url ?>', <?= $lti_data ?>, function () {
            // on success
            $('#oc_lti_success_<?= $config_id ?>').show();
        }, function () {
            // on error
            $('#oc_lti_error_<?= $config_id ?>').show();
        });
        </script>
        <? endif ?>

    </fieldset>
    <? endforeach ?>

    <footer>
        <?= Button::createAccept($_('Übernehmen')) ?>

        <? if ($add_server) : ?>
            <?= LinkButton::createCancel($_('Neuen Opencast-Server hinzufügen'), $controller->url_for('admin/add_server/')) ?>
        <? endif ?>
    </footer>
</form>
