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
            'text' => _("Hier können die entsprechenden Stud.IP Ressourcen mit den Capture Agents aus dem Opencast Matterhorn System verknüpfen.")
        ))
    ));
    $infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>
<script language="JavaScript">
OC.initAdmin();
</script>

<h3> <?= _("Zuweisung der Capture Agents") ?> </h3>


<? foreach ($resources as $resource) :?>
    <div class="resources">
        <div class="topic resource"> <?= $resource['name'] ?> </div>
        <div class="resource rcontent">
            <form action="<?= PluginEngine::getLink('opencast/admin/update_resource/') ?>" method=post>
                <?= CSRFProtection::tokenTag() ?>
                <input type="hidden" name="action" value="add"/>
                <select name="<?=$resource['resource_id']?>">
                    <? foreach ($agents as $agent) : ?>
                    <option value="<?= $agent->agent->name ?>"> <?= $agent->agent->name ?> </option>
                    <? endforeach; ?>
                </select>
                <div class="form_submit">
                    <?= makebutton("uebernehmen","input") ?>
                    <a href="<?=PluginEngine::getLink('opencast/admin/resources/')?>"><?= makebutton("abbrechen")?></a>
                </div>
            </form>
        </div>
    </div>

<? endforeach; ?>



