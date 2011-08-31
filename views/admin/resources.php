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
                <? $assigned_agents = OCModel::getCAforResource($resource['resource_id']); ?>
                <? if(!empty($assigned_agents)) :?>
                    <ul class="form_list">
                        <? foreach($assigned_agents as $aa) : ?>
                            <li>
                                <?= $aa['capture_agent'] ?>
                                <? foreach ($agents as $agent) : ?>
                                    <? if($agent['name'] ==  $aa['capture_agent']):?>
                                        <? if($agent['state'] == 'idle') :?>
                                            <?= Assets::img('icons/16/blue/pause.png', array('title' => _("Idle"))) ?>
                                        <? else: ?>
                                            <?= Assets::img('icons/16/blue/video.png', array('title' => _("Idle"))) ?>
                                        <? endif; ?>
                                    <? endif; ?>
                                <? endforeach; ?>
                                <a href="<?=PluginEngine::getLink('opencast/admin/remove_ca/'. $resource['resource_id']
                                            .'/'.$aa['capture_agent'])?>">
                                    <?= Assets::img('icons/16/blue/trash.png', array('title' => _("Verknüpfung entfernen."))) ?>
                                </a>

                            </li>
                        <? endforeach; ?>
                    </ul>

                <? else :?>
                    <input type="hidden" name="action" value="add"/>
                    <select name="<?=$resource['resource_id']?>">
                        <? foreach ($agents as $agent) : ?>
                            <? $agent_name = $agent['name']; ?>
                            <? if(!empty($assigned_cas)) :?>
                                <? foreach($assigned_cas as $aca) : ?>
                                    <? if($aca['capture_agent'] == $agent_name ) :?>
                                        <option disabled selected> <?=_("Kein CA mehr verfügbar")?> </option>
                                    <? else : ?>
                                        <option value="<?= $agent_name ?>" > <?=$agent_name?> </option>
                                    <? endif; ?>
                                <? endforeach ; ?>
                            <? else : ?>
                                    <option value="<?= $agent_name ?>" > <?=$agent_name?> </option>
                            <? endif; ?>
                        <? endforeach; ?>
                    </select>
                    <div class="form_submit">
                        <?= makebutton("uebernehmen","input") ?>
                        <a href="<?=PluginEngine::getLink('opencast/admin/resources/')?>"><?= makebutton("abbrechen")?></a>
                    </div>
                <? endif; ?>
            </form>
        </div>
    </div>

<? endforeach; ?>



