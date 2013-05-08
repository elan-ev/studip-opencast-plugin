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
            'text' => _("Hier können die Anbindung zum Opencast Matterhorn System verwaltet werden. Geben Sie die jeweiligen URLs zu den REST-Sevices, sowie die dementsprechenen Zugangsdaten an.")
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
  <?=_("Tragen Sie hier die jeweilgen Pfade zu den Matterhorn REST-Endpoints ein.")?>
</span> -->

<form class="conf-form" action="<?= PluginEngine::getLink('opencast/admin/update/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <fieldset class="conf-form-field">
        <legend><?=_("Globale Opencast Matterhorn Einstellungen")?></legend>
        <p><?=_("Tragen Sie hier die den Pfad zu den Matterhorn REST-Endpoints ein.")?></p>
        
        <label  for="info_url"><?=_("Service-URL")?>:</label><br>
        <input type="text" name="info_url" value="<?=$info_url?>" size="50" placeholder="SERIES_ENDPOINT_URL"><br>
         
        <label for="info_user"><?=_("Nutzerkennung")?>:</label><br>
        <input type="text" name="info_user" value="<?=$info_user?>" size="50" placeholder="SERIES_ENDPOINT_USER"><br>
        
        <label for="info_password"><?=_("Passwort")?>:</label><br>
        <input type="password" name="info_password" value="<?=$info_password?>" size="50"><br>
        
          <div>
         <?= Button::createAccept(_('Übernehmen')) ?>
         <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/admin/config/')) ?>
         </div>
        
        
    </fieldset>
</form>

<!-- 

<form style="padding-top:25px;" action="<?= PluginEngine::getLink('opencast/admin/update/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <div id="admin-accordion">
       <h3><?=_("Info Service")?></h3>
       <div class="admin-accordion-content">
            <label class="form_label" for="info_url"><?=_("Service-URL")?>:</label>
             <input id="group_name" type="text" name="info_url" value="<?=$info_url?>" size="50" placeholder="SERIES_ENDPOINT_URL"><br>
             <label class="form_label" for="info_user"><?=_("Nutzerkennung")?>:</label>
             <input id="group_name" type="text" name="info_user" value="<?=$info_user?>" size="50" placeholder="SERIES_ENDPOINT_USER"><br>
             <label class="form_label" for="info_password"><?=_("Passwort")?>:</label>
             <input id="group_name" type="password" name="info_password" value="<?=$info_password?>" size="50"><br>
       </div>  
    </div>

     <div class="form_submit">
    <?= Button::createAccept(_('Übernehmen')) ?>
    <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/admin/config/')) ?>
    </div>
</form>
-->






<?php
