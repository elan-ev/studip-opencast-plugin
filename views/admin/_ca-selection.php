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
<td> 
     <? foreach ($agents as $agent_wrapper) : ?>
         <? $agent = $agent_wrapper->agent[0]; ?>
         <? if($agent->name ==  $assigned_agents['capture_agent']):?>
             <? if($agent->state == 'idle') :?>
                <?= Assets::img('icons/16/blue/pause.png', array('title' => _("Idle"))) ?>
             <? elseif($agent->state = 'unknown') : ?>
                <?= Assets::img('icons/16/blue/question.png', array('title' => _("Status unbekannt"))) ?>
            <? else: ?>
                <?= Assets::img('icons/16/blue/video.png', array('title' => _("Beschäftigt"))) ?>
            <? endif; ?>
        <? endif; ?>
        <td>
            <a href="<?=PluginEngine::getLink('opencast/admin/remove_ca/'. $resource['resource_id']
                        .'/'. $agent->name)?>">
                <?= Assets::img('icons/16/blue/trash.png', array('title' => _("Verknüpfung entfernen."))) ?>
            </a>     
        </td>
     <? endforeach; ?>
</td>
<? else :?>
<td>
    <input type="hidden" name="action" value="add"/>
    <select name="<?=$resource['resource_id']?>">
        <option value="" disabled selected><?=_("Bitte wählen Sie einen CA.")?></option>
        <? if($agents) : ?>            
            <? foreach ($agents->agents as $agent) : ?>
                <? $agent_name = $agent[0]->name; ?>
                <? if(!empty($assigned_cas)) :?>
                    <? foreach($assigned_cas as $aca) : ?>
                        <? if($aca['capture_agent'] == $agent_name ) :?>
                            <option disabled selected> <?=_("Kein CA mehr verfügbar")?> </option>
                        <? else : ?>
                            <option value="<?= $agent_name ?>" > <?=$agent_name?> </option>
                        <? endif; ?>
                    <? endforeach ; ?>
                <? else : ?>
                        <option value="<?= $agent_name ?>" > <?=$agent_name?></option>
                <? endif; ?>
            <? endforeach; ?>
        <? else :?>
        <?=_("Nüscht")?>
        <? endif;?>
    </select>
</td>
<td></td>
<td></td>
<? endif; ?>