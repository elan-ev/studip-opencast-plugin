<? use Studip\Button, Studip\LinkButton; ?>
<form class="conf-form default" action="<?= PluginEngine::getLink('opencast/admin/precise_update/') ?>" method=post>
    <fieldset class="conf-form-field collapsable collapsed">
        <legend><?= $_('Globale Einstellungen'); ?></legend>

            <? foreach (Configuration::instance()->get_entries_for_display() as $name => $data) : ?>
                <label title="Name der Einstellung: <?= $name ?>">
                    <?= $data['description'] ?>
                    <input type="<?= $data['type'] ?>" value="<?= $data['value'] ?>" name="precise_config[-1][<?= $name ?>]">
                </label>
            <? endforeach ?>

            <? if (Config::get()->OPENCAST_SHOW_TOS) : ?>
            <?= I18N::textarea("tos", new I18NString($config[1]['tos'], null, [
                    'object_id' => 1,
                    'table' => 'oc_config',
                    'field' => 'tos'
                ]), ['class' => 'add_toolbar wysiwyg']) ?>
            <? endif ?>

            <?= Button::createAccept($_('Übernehmen')) ?>

            <label>
                <?= $_('In der Datenbank eingetragenen Konfigurationen (IDs): ') ?>
                <b><?= implode(', ', Configuration::registered_base_config_ids()) ?></b>
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
</form>

<form class="conf-form default" action="<?= PluginEngine::getLink('opencast/admin/update/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <?php
        $config_ids = [];
        if($global_config['number_of_configs']>0){
            $config_ids = range(1,$global_config['number_of_configs']);
        }
    ?>
    <? foreach ($config_ids as $config_id): ?>
    <fieldset class="conf-form-field">
        <legend>
            <?= $_("Opencast Server Einstellungen")." (ID:$config_id)" ?>
        </legend>

        <label>
            <?=$_("Basis URL zur Opencast Installation")?>
            <input type="text" name="config[<?= $config_id ?>][url]"
                value="<?= $config[$config_id]['service_url'] ?>"
                placeholder="http://opencast.url">
        </label>

        <label>
            <?=$_("Nutzerkennung")?>
            <input type="text" name="config[<?= $config_id ?>][user]"
                value="<?= $config[$config_id]['service_user'] ?>"
                placeholder="ENDPOINT_USER">
        </label>

        <label>
            <?= $_("Passwort") ?>
            <input type="password" name="config[<?= $config_id ?>][password]"
                value="<?= $config[$config_id]['service_password'] ?>"
                placeholder="ENDPOINT_USER_PASSWORD">
        </label>

        <details>
            <summary>Weitere Einstellungen</summary>
            <? $special_config = Configuration::instance($config_id)->get_entries_for_display(); ?>
            <? foreach (Configuration::instance()->get_entries_for_display() as $name => $data){
                if (in_array($name, ['number_of_configs'])) continue;
                $special_config_exists = isset($special_config[$name]); ?>
                <label title="Name der Einstellung: <?= $name ?>">
                    <?= ($special_config_exists ? $special_config[$name]['description'] : $data['description']) ?>

                    <input type="<?= ($special_config_exists ? $special_config[$name]['type'] : $data['type']) ?>"
                        value="<?= ($special_config_exists ? $special_config[$name]['value'] : $data['value']) ?>"
                        name="config[<?= $config_id ?>][precise][<?= $name ?>]"
                    >
                </label>
            <? } ?>
        </details>

        <? if ($config[$config_id]['service_version']) : ?>
        <label>
            <?= $_('Opencast Basisversion') ?><br>
            <?= $config[$config_id]['service_version']  ?>
        </label>
        <? endif ?>

    </fieldset>
    <? endforeach ?>

    <footer>
        <?= Button::createAccept($_('Übernehmen')) ?>
        <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/admin/config/')) ?>
    </footer>
</form>
