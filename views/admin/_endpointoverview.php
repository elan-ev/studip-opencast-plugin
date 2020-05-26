<table border="0" cellspacing="5" cellpadding="5" class="default">
    <caption>
        <h1><?= htmlReady($config['service_url']) ?></h1>
    </caption>
    <thead>
        <tr>
            <th><?= $_("Endpoint") ?></th>
            <th><?= $_("Host") ?></th>
        </tr>
    </thead>
    <tbody>

        <? foreach ($endpoints as $endpoint) : ?>
            <? if ($endpoint['config_id'] != $show_config_id) continue ?>
            <tr>
                <td>
                    <?= $endpoint['service_type'] ?>
                </td>
                <td>
                    <?= $endpoint['service_url'] ?>
                </td>
            </tr>
        <? endforeach; ?>
    </tbody>
</table>
