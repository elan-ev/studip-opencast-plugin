<? use Studip\Button, Studip\LinkButton; ?>
<form class="conf-form default" action="<?= PluginEngine::getLink('opencast/admin/update/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>

    <? foreach ([1,2] as $config_id): ?>
    <fieldset class="conf-form-field">
        <legend>
            <? if ($config_id == 1) : ?>
                <?= $_("Opencast Server Einstellungen (Aufzeichnung)") ?>
            <? else : ?>
                <?=$_("Optionale Opencast Server Einstellungen (Lesezugriff)")?>
            <? endif ?>
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

        <label>
            <?= $_("Zeitpuffer (in Sekunden)") ?>
            <?= tooltipIcon($_('Zur Verhinderung von Aufzeichnungsüberlappungen bei Vorausplanung.'))?>
            <input name="config[<?= $config_id ?>][puffer]" type="number" min="1" step="1" value="<?= $config[$config_id]['schedule_time_puffer_seconds'] ?>">
        </label>

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
