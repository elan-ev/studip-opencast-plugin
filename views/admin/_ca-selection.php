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
                    <?= new Icon('pause', 'clickable', array('title' => $_("Idle"))) ?>
                <? elseif($agent->state = 'unknown') : ?>
                    <?= new Icon('question', 'clickable', array('title' => $_("Status unbekannt"))) ?>
                <? else: ?>
                    <?= new Icon('video', 'clickable', array('title' => $_("Beschäftigt"))) ?>
                <? endif; ?>

            </td>
            <td>
                <a href="<?=PluginEngine::getLink('opencast/admin/remove_ca/'. $resource['resource_id']
                        .'/'. $agent->name)?>">
                    <?= new Icon('trash', 'clickable', array(
                        'title' => $_("Verknüpfung entfernen.")
                    )) ?>
                </a>
            </td>
        <? endif; ?>
    <? endforeach; ?>
<? else :?>
<td>
    <input type="hidden" name="action" value="add"/>
    <? if (!empty($available_agents)) : ?>
        <select name="<?=$resource['resource_id']?>">
            <option value="" disabled selected><?=$_("Bitte wählen Sie einen CA.")?></option>

            <? foreach ($available_agents as $agent) : ?>

                <? if(isset($agent)) : ?>
                    <option value="<?= $agent->name ?>" > <?=$agent->name?></option>
                <? endif; ?>
            <? endforeach ; ?>
        </select>
    <? else: ?>
        <?= $_("Kein (weiterer) CA verfügbar") ?> </option>
    <? endif;?>
</td>

<td>
    <? if (!empty($available_agents)) : ?>
    <select name="workflow">
        <option value="" disabled selected><?=$_("Bitte wählen Sie einen Worflow aus.")?></option>

        <? if ($definitions): ?>
            <? foreach ($definitions->definitions->definition as $definition) :?>
                <? if (is_object($definition->tags)) : ?>
                    <? if (is_array($definition->tags->tag) &&
                        (in_array('schedule', $definition->tags->tag) ||
                        in_array('schedule-ng', $definition->tags->tag))
                    ) :?>
                    <option  value="<?= $definition->id ?>"><?= $definition->id ?></option>
                    <? elseif ($definition->tags->tag == 'schedule' || $definition->tags->tag == 'schedule-ng') : ?>
                        <option value="<?= $definition->id ?>"><?= $definition->id ?></option>
                    <? endif;?>
                <? endif; ?>
            <? endforeach ?>
        <? else : ?>
            <option disabled selected> <?=$_("Kein Workflow verfügbar")?> </option>
        <? endif ?>
    </select>

    <? else : ?>
        -
    <? endif ?>
</td>
<td></td>
<td></td>
<? endif; ?>
