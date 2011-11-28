<?
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
<h3>Globale Opencast Matterhorn Einstellungen</h3>
<span>
  <?=_("Tragen Sie hier die jeweilgen Pfade zu den Matterhorn REST-Endpoints ein.")?>
</span>
<form style="padding-top:25px;" action="<?= PluginEngine::getLink('opencast/admin/update/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <div id="admin-accordion">
       <h3><?=_("Series Service")?></h3>
       <div class="admin-accordion-content">
            <label class="form_label" for="series_url"><?=_("Service-URL")?>:</label>
             <input id="group_name" type="text" name="series_url" value="<?=$series_url?>" size="50" placeholder="SERIES_ENDPOINT_URL">
             <label class="form_label" for="series_user"><?=_("Nutzerkennung")?>:</label>
             <input id="group_name" type="text" name="series_user" value="<?=$series_user?>" size="50" placeholder="SERIES_ENDPOINT_USER">
             <label class="form_label" for="series_password"><?=_("Passwort")?>:</label>
             <input id="group_name" type="password" name="series_password" value="<?=$series_password?>" size="50">
       </div>
       <h3><?=_("Search Service")?></h3>
       <div class="admin-accordion-content">
            <label class="form_label" for="search_url"><?=_("Service-URL")?>:</label>
            <input id="group_name" type="text" name="search_url" value="<?=$search_url?>" size="50" placeholder="SERIES_ENDPOINT_URL">
              <label class="form_label" for="search_user"><?=_("Nutzerkennung")?>:</label>
             <input id="group_name" type="text" name="search_user" value="<?=$search_user?>" size="50" placeholder="SERIES_ENDPOINT_USER">
             <label class="form_label" for="search_password"><?=_("Passwort")?>:</label>
        <input id="group_name" type="password" name="search_password" value="<?=$search_password?>" size="50">
       </div>
       <h3><?=_("Scheduling Service")?></h3>
       <div class="admin-accordion-content">
            <label class="form_label" for="scheduling_url"><?=_("Service-URL")?>:</label>
            <input id="group_name" type="text" name="scheduling_url" value="<?=$scheduling_url?>" size="50" placeholder="SCHEDULING_ENDPOINT_URL">
            <label class="form_label" for="scheduling_user"><?=_("Nutzerkennung")?>:</label>
            <input id="group_name" type="text" name="scheduling_user" value="<?=$scheduling_user?>" size="50" placeholder="SCHEDULING_ENDPOINT_USER">
            <label class="form_label" for="scheduling_password"><?=_("Passwort")?>:</label>
            <input id="group_name" type="password" name="scheduling_password" value="<?=$scheduling_password?>" size="50">
       </div>
       <h3><?=_("Capture Admin Service")?></h3>
       <div class="admin-accordion-content">
              <label class="form_label" for="captureadmin_url"><?=_("Service-URL")?>:</label>
              <input id="group_name" type="text" name="captureadmin_url" value="<?=$captureadmin_url?>" size="50" placeholder="CAPTUREADMIN_ENDPOINT_URL">
              <label class="form_label" for="captureadmin_user"><?=_("Nutzerkennung")?>:</label>
              <input id="group_name" type="text" name="captureadmin_user" value="<?=$captureadmin_user?>" size="50" placeholder="CAPTUREADMIN_ENDPOINT_USER">
              <label class="form_label" for="captureadmin_password"><?=_("Passwort")?>:</label>
              <input id="group_name" type="password" name="captureadmin_password" value="<?=$captureadmin_password?>" size="50">
       </div>
    </div>

     <div class="form_submit">
    <?= makebutton("uebernehmen","input") ?>
    <a href="<?=PluginEngine::getLink('opencast/admin/config/')?>"><?= makebutton("abbrechen")?></a>
    </div>
</form>   






<?php
