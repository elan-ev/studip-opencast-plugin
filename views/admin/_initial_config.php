<? use Studip\Button,
    Studip\LinkButton,
    Opencast\Configuration,
    Opencast\Constants;
?>
<form class="default" action="<?= $controller->url_for('admin/update/') ?>" method=post>
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
    <fieldset class="collapsable">
        <legend>
            <?= $_('Opencast Server Einstellungen')." (ID: $config_id) - "
                . $_('OC Version') . ": ". $config[$config_id]['service_version'] .".x" ?>
        </legend>

        <label>
            <span class="required">
                <?=$_('Basis URL zur Opencast Installation')?>
            </span>

            <input type="text" name="config[<?= $config_id ?>][url]"
                value="<?= $config[$config_id]['service_url'] ?>"
                placeholder="http://opencast.url">
        </label>

        <label>
            <span class="required">
                <?=$_('Nutzerkennung')?>
            </span>

            <input type="text" name="config[<?= $config_id ?>][user]"
                value="<?= $config[$config_id]['service_user'] ?>"
                placeholder="ENDPOINT_USER">
        </label>

        <label>
            <span class="required">
                <?= $_('Passwort') ?>
            </span>

            <input type="password" name="config[<?= $config_id ?>][password]"
                value="<?= $config[$config_id]['service_password'] ?>"
                placeholder="ENDPOINT_USER_PASSWORD">
        </label>

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
    </fieldset>
    <? endforeach ?>

    <footer>
        <?= Button::createAccept($_('Übernehmen')) ?>
        <?= LinkButton::createCancel($_('Neuen Opencast-Server hinzufügen'), $controller->url_for('admin/add_server/')) ?>
    </footer>
</form>
