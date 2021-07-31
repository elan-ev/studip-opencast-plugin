<?
$assigned_agents = OCModel::getCAforResource($resource['resource_id']);
?>


<td>
    <?= htmlReady($resource['name']) ?>
</td>
<? if (!empty($assigned_agents)) : ?>
    <td>
        <?= htmlReady($assigned_agents['capture_agent']) ?>
    </td>
    <td>
        <?= htmlReady($assigned_agents['workflow_id']) ?>
    </td>
    <? foreach ($agents as $key => $agent) : ?>
        <? if (in_array($agent->name, $assigned_agents)): ?>
            <td>
                <? if ($agent->state == 'idle') : ?>
                    <?= Icon::create('pause', Icon::ROLE_CLICKABLE, ['title' => $_('Idle')]) ?>
                <? elseif ($agent->state == 'unknown') : ?>
                    <?= Icon::create('question', Icon::ROLE_CLICKABLE, ['title' => $_('Status unbekannt')]) ?>
                <? else: ?>
                    <?= Icon::create('video', Icon::ROLE_CLICKABLE, ['title' => $_('Beschäftigt')]) ?>
                <? endif; ?>
            </td>
            <td>
                <a href="<?= $controller->url_for('admin/remove_ca/' . $resource['resource_id'] . '/' . $agent->name) ?>">
                    <?= Icon::create('trash', Icon::ROLE_CLICKABLE, ['title' => $_('Verknüpfung entfernen')]) ?>
                </a>
            </td>
        <? endif ?>
    <? endforeach ?>
<? else : ?>
    <td>
        <input type="hidden" name="action" value="add"/>
        <? if (!empty($available_agents)) : ?>
            <select name="<?= $resource['resource_id'] ?>">
                <option value="" disabled selected><?= $_('Bitte wählen Sie einen CA.') ?></option>
                <? foreach ($available_agents as $agent) : ?>
                    <? if (isset($agent)) : ?>
                        <option value="<?= $agent->name ?>"> <?= htmlReady($agent->name) ?></option>
                    <? endif; ?>
                <? endforeach; ?>
            </select>
        <? else: ?>
            <?= $_('Kein (weiterer) CA verfügbar') ?> </option>
        <? endif; ?>
    </td>

    <td>
        <? if (!empty($available_agents)) : ?>
            <select name="workflow">
                <option value="" disabled selected><?= $_('Bitte wählen Sie einen Workflow aus.') ?></option>
                <? if ($definitions): ?>
                    <? foreach ($definitions->definitions->definition as $definition) : ?>
                        <? if (is_object($definition->tags)) : ?>
                            <? if (is_array($definition->tags->tag) &&
                                (in_array('schedule', $definition->tags->tag) ||
                                    in_array('schedule-ng', $definition->tags->tag))
                            ) : ?>
                                <option value="<?= $definition->id ?>">
                                    <?= htmlReady($definition->title) ?> (<?= htmlReady($definition->id) ?>)
                                </option>
                            <? elseif ($definition->tags->tag == 'schedule' || $definition->tags->tag == 'schedule-ng') : ?>
                                <option value="<?= $definition->id ?>">
                                    <?= htmlReady($definition->title) ?> (<?= htmlReady($definition->id) ?>)
                                </option>
                            <? endif; ?>
                        <? endif; ?>
                    <? endforeach ?>
                <? else : ?>
                    <option disabled selected> <?= $_('Kein Workflow verfügbar') ?> </option>
                <? endif ?>
            </select>
        <? else : ?>
            -
        <? endif ?>
    </td>
    <td></td>
    <td></td>
<? endif ?>
