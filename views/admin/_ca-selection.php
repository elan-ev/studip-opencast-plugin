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
    <?=$assigned_agents['workflow_id']?>
    </td>
    <? foreach ($agents as $key => $agent) : ?>
        <? if(in_array($agent->name, $assigned_agents)):?>
            <td>
                    <? if($agent->state == 'idle') :?>
                        <?= Assets::img('icons/16/blue/pause.png', array('title' => $_("Idle"))) ?>
                    <? elseif($agent->state = 'unknown') : ?>
                        <?= Assets::img('icons/16/blue/question.png', array('title' => $_("Status unbekannt"))) ?>
                    <? else: ?>
                        <?= Assets::img('icons/16/blue/video.png', array('title' => $_("Besch�ftigt"))) ?>
                    <? endif; ?>

            </td>
            <td>
                <a href="<?=PluginEngine::getLink('opencast/admin/remove_ca/'. $resource['resource_id']
                        .'/'. $agent->name)?>">
                        <?= Assets::img('icons/16/blue/trash.png', array('title' => $_("Verkn�pfung entfernen."))) ?>
                </a>
            </td>
        <? endif; ?>
    <? endforeach; ?>
<? else :?>
<td>
    <input type="hidden" name="action" value="add"/>
    <select name="<?=$resource['resource_id']?>" required>
        <option value="" disabled selected><?=$_("Bitte w�hlen Sie einen CA.")?></option>
        <? if($available_agents) : ?>


            <? foreach ($available_agents as $agent) : ?>

                <? if(isset($agent)) : ?>
                    <option value="<?= $agent->name ?>" > <?=$agent->name?></option>
                <? endif; ?>
            <? endforeach ; ?>
        <? else: ?>
            <option disabled selected> <?=$_("Kein CA verf�gbar")?> </option>
        <? endif;?>
    </select>
</td>
<td>
    <select name="workflow" required>
        <option value="" disabled selected><?=$_("Bitte wählen Sie einen Worflow aus.")?></option>
        <? if($definitions){ foreach($definitions->definitions->definition as $definition){ ?>
            <? if(is_object($definition->tags)) : ?>
                <? if(is_array($definition->tags->tag) &&
                    in_array('schedule', $definition->tags->tag) &&
                    in_array('schedule-ng', $definition->tags->tag)) :?>
                <option  value="<?= $definition->id ?>"><?= $definition->id ?></option>
                <? elseif($definition->tags->tag == 'schedule' || $definition->tags->tag == 'schedule-ng') :?>
                    <option  value="<?= $definition->id ?>"><?= $definition->id ?></option>
                <? endif;?>
            <? endif; ?>
        <? } } else { ?>
        <option disabled selected> <?=$_("Kein Workflow verf�gbar")?> </option>
        <? } ?>
    </select>
</td>
<td></td>
<td></td>
<? endif; ?>
