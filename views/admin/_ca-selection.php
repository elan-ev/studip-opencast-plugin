<?
    $assigned_agents = OCModel::getCAforResource($resource['resource_id']);
?>
<td>
    <?= $resource['name'] ?>
</td>
<? if(!empty($assigned_agents)) :?>
    <td>   
        <?=$assigned_agents['capture_agent']?>
    </td>
    <? foreach ($agents->agents as $key => $agent) : ?>
        <? if(in_array($agent->name, $assigned_agents)):?>
            <td>
                    <? if($agent->state == 'idle') :?>
                        <?= Assets::img('icons/16/blue/pause.png', array('title' => _("Idle"))) ?>
                    <? elseif($agent->state = 'unknown') : ?>
                        <?= Assets::img('icons/16/blue/question.png', array('title' => _("Status unbekannt"))) ?>
                    <? else: ?>
                        <?= Assets::img('icons/16/blue/video.png', array('title' => _("Beschäftigt"))) ?>
                    <? endif; ?>
        
            </td>
            <td>
                <a href="<?=PluginEngine::getLink('opencast/admin/remove_ca/'. $resource['resource_id']
                        .'/'. $agent->name)?>">
                        <?= Assets::img('icons/16/blue/trash.png', array('title' => _("Verknüpfung entfernen."))) ?>
                </a>     
            </td>
        <? endif; ?>
    <? endforeach; ?>
<? else :?>
<td>
    <input type="hidden" name="action" value="add"/>

    <select name="<?=$resource['resource_id']?>">
        <option value="" disabled selected><?=_("Bitte wählen Sie einen CA.")?></option>
        <? if($agents) : ?>

            <? foreach ($available_agents->agents as $agent) : ?>
                <? if(isset($agent)) : ?>
                    <option value="<?= $agent->name ?>" > <?=$agent->name?></option>
                <? endif; ?>
            <? endforeach ; ?>
        <? else: ?>
            <option disabled selected> <?=_("Kein CA mehr verfügbar")?> </option>
        <? endif;?>
    </select>
</td>
<td></td>
<td></td>
<? endif; ?>