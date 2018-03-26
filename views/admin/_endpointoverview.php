<? use Studip\Button, Studip\LinkButton; ?>
<form class="conf-form" action="<?= PluginEngine::getLink('opencast/admin/update_endpoints/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>

    <table border="0" cellspacing="5" cellpadding="5" class="default">
        <tr>
            <th><?= $_("Endpoint") ?></th>
            <th><?= $_("Host") ?></th>
        </tr>

        <? foreach($endpoints as $endpoint) :?>
        <tr>
            <td>
                <?= $endpoint['service_type']?>
            </td>
            <td>
                <?=$endpoint['service_url']?>
            </td>
        </tr>
        <? endforeach; ?>
    </table>
</form>
