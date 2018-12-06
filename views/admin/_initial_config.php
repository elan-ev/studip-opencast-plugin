
<? use Studip\Button, Studip\LinkButton; ?>
<form class="conf-form default" action="<?= PluginEngine::getLink('opencast/admin/precise_update/') ?>" method=post>
    <fieldset class="conf-form-field">
        <legend><?= $_('Globale Einstellungen'); ?></legend>
        <details>
            <summary>Einstellungen</summary>
            <? foreach (Configuration::i()->get_entries_for_display() as $name=>$data){?>
                <label title="Name der Einstellung: <?= $name ?>">
                    <?= $data['description'] ?>
                    <input type="<?= $data['type'] ?>" value="<?= $data['value'] ?>" name="precise_config[-1][<?= $name ?>]">
                </label>
            <? } ?>
            <?= Button::createAccept($_('Übernehmen')) ?>
        </details>
        <br><?= $_('Anzahl der in der Datenbank eingetragenen Konfigurationen: ').'<b>'.count($configs_in_database).'</b>' ?>
    </fieldset>
</form>

<form class="conf-form default" action="<?= PluginEngine::getLink('opencast/admin/update/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <? foreach (range(1,$global_config['number_of_configs']) as $config_id): ?>
    <fieldset class="conf-form-field">
        <legend>
            <?= $_("Opencast Server Einstellungen")."(ID:$config_id)" ?>
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
            <? $special_config = Configuration::i($config_id)->get_entries_for_display(); ?>
            <? foreach (Configuration::i()->get_entries_for_display() as $name=>$data){
                if($name == 'number_of_configs'){continue;}
                $special_config_exists = isset($special_config[$name]); ?>
                <label title="Name der Einstellung: <?= $name ?>">
                    <?= ($special_config_exists?$special_config[$name]['description']:$data['description']) ?>
                    <input type="<?= ($special_config_exists?$special_config[$name]['type']:$data['type']) ?>" value="<?= ($special_config_exists?$special_config[$name]['value']:$data['value']) ?>" name="config[<?= $config_id ?>][precise][<?= $name ?>]">
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
