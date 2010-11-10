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
?>   

<h1>Globale Opencast Matterhorn Einstellungen</h1>

<form action="<?= PluginEngine::getLink('opencast/admin/update_config/') ?>" method=post>
    <ul class="form_list">
        <li class="form_item">
            <label class="form_label" for="series_url"><?=_("Series-Service URL")?>:</label>
            <input id="group_name" type="text" name="series_url" value="<?=$series_url?>" size="50">
        </li>
        <li class="form_item">
            <label class="form_label" for="search_url"><?=_("Search-Service URL")?>:</label>
            <input id="group_name" type="text" name="search_url" value="<?=$search_url?>" size="50">
        </li>
        <li class="form_item">
            <label class="form_label" for="group_name"><?=_("Password")?>:</label>
            <input id="group_name" type="text" name="user" value="<?=$user?>" size="50">
        </li>
        <li class="form_item">
            <label class="form_label" for="group_name"><?=_("Passwort")?>:</label>
            <input id="group_name" type="text" name="password" value="<?=$password?>" size="50">
        </li>
     </ul>
     <input id="group_name" type="hidden" name="config_id" value="<?=$config_id?>">
     <div class="form_submit">
    <?= makebutton("uebernehmen","input") ?>
    <a href="<?=PluginEngine::getLink('opencast/admin/config/')?>"><?= makebutton("abbrechen")?></a>
    </div>
</form>   






<?php
