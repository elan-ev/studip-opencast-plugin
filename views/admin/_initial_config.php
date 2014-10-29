<? use Studip\Button, Studip\LinkButton; ?>
<form class="conf-form" action="<?= PluginEngine::getLink('opencast/admin/update/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <fieldset class="conf-form-field">
        <legend><?=_("Globale Opencast Matterhorn Einstellungen")?></legend>
        <p><?=_("Tragen Sie hier die den Pfad Ihres Matterhorn Basis System ein.")?></p>
        
        <label  for="info_url"><?=_("Basis URL zur Opencast Matterhorn Installation")?>:</label><br>
        <input type="text" name="info_url" value="<?=$info_url?>" size="50" placeholder="http://matterhorn.url" required><br>
         
        <label for="info_user"><?=_("Nutzerkennung")?>:</label><br>
        <input type="text" name="info_user" value="<?=$info_user?>" size="50" placeholder="ENDPOINT_USER" required><br>
        
        <label for="info_password"><?=_("Passwort")?>:</label><br>
        <input type="password" name="info_password" value="<?=$info_password?>" size="50" placeholder="ENDPOINT_USER_PASSWORD" required><br>
        
          <div>
         <?= Button::createAccept(_('Übernehmen')) ?>
         <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/admin/config/')) ?>
         </div>

    </fieldset>
</form>