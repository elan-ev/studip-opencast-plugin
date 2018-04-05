<? use Studip\Button, Studip\LinkButton; ?>
<form class="conf-form" action="<?= PluginEngine::getLink('opencast/admin/update/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <fieldset class="conf-form-field">
        <legend><?=$_("Opencast Server Einstellungen (Aufzeichnung)")?></legend>
        <p><?=$_("Tragen Sie hier den Pfad Ihres Opencast Basis-Systems ein.")?></p>

        <label  for="info_url"><?=$_("Basis URL zur Opencast Installation")?>:</label><br>
        <input type="text" name="info_url" value="<?=$info_url?>" size="50" placeholder="http://opencast.url" required><br>

        <label for="info_user"><?=$_("Nutzerkennung")?>:</label><br>
        <input type="text" name="info_user" value="<?=$info_user?>" size="50" placeholder="ENDPOINT_USER" required><br>

        <label for="info_password"><?=$_("Passwort")?>:</label><br>
        <input type="password" name="info_password" value="<?=$info_password?>" size="50" placeholder="ENDPOINT_USER_PASSWORD" required><br>


    </fieldset>

    <fieldset class="conf-form-field">
        <legend><?=$_("Optionale Opencast Server Einstellungen (Lesezugriff)")?></legend>
        <p><?=$_("Tragen Sie hier den Pfad Ihres optionalen Opencast Systems ein.")?></p>

        <label  for="slave_url"><?=$_("Basis URL zur optionalen Opencast Installation")?>:</label><br>
        <input type="text" name="slave_url" value="<?=$slave_url?>" size="50" placeholder="http://opencast.url"><br>

        <label for="slave_user"><?=$_("Nutzerkennung")?>:</label><br>
        <input type="text" name="slave_user" value="<?=$slave_user?>" size="50" placeholder="ENDPOINT_USER"><br>

        <label for="slave_password"><?=$_("Passwort")?>:</label><br>
        <input type="password" name="slave_password" value="<?=$slave_password?>" size="50" placeholder="ENDPOINT_USER_PASSWORD"><br>

          <div>
         <?= Button::createAccept($_('Übernehmen')) ?>
         <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/admin/config/')) ?>
         </div>

    </fieldset>
</form>
