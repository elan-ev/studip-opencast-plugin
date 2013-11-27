<?
    use Studip\Button, Studip\LinkButton;

    if ($success = $flash['success']) {
        echo MessageBox::success($success);
    }
    if ($error = $flash['error']) {
        echo MessageBox::error($error);
    }
    if ($flash['question']) {
        echo $flash['question'];
    }


    $infobox_content = array(array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(array(
            'icon' => 'icons/16/black/info.png',
            'text' => _("Hier kann die Anbindung zum Opencast Matterhorn System verwaltet werden.")
        ))
    ));
    $infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>
<script language="JavaScript">
OC.initAdmin();
</script>
<!--
<h3>Globale Opencast Matterhorn Einstellungen</h3>
<span>
  <?=_("Tragen Sie hier den Pfad zum Matterhorn Runtime Information REST-Endpoint ein.")?>
</span> -->

<form class="conf-form" action="<?= PluginEngine::getLink('opencast/admin/update/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <fieldset class="conf-form-field">
        <legend><?=_("Globale Opencast Matterhorn Einstellungen")?></legend>
        <p><?=_("Tragen Sie hier die den Pfad zu den Matterhorn REST-Endpoints ein.")?></p>
        
        <label  for="info_url"><?=_("Service-URL")?>:</label><br>
        <input type="text" name="info_url" value="<?=$info_url?>" size="50" placeholder="INFO_ENDPOINT_URL"><br>
         
        <label for="info_user"><?=_("Nutzerkennung")?>:</label><br>
        <input type="text" name="info_user" value="<?=$info_user?>" size="50" placeholder="INFO_ENDPOINT_USER"><br>
        
        <label for="info_password"><?=_("Passwort")?>:</label><br>
        <input type="password" name="info_password" value="<?=$info_password?>" size="50"><br>
        
          <div>
         <?= Button::createAccept(_('Übernehmen')) ?>
         <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/admin/config/')) ?>
         </div>
        
        
    </fieldset>
</form>