<? use Studip\Button, Studip\LinkButton; ?>
<form class="conf-form" action="<?= PluginEngine::getLink('opencast/admin/update_endpoints/') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>

        <table border="0" cellspacing="5" cellpadding="5" class="default">
            <tr><th><?=$_("Endpoint")?></th><th><?=$_("Host")?></th><th><?=$_("Status")?></th></tr>
            <? foreach($endpoints as $endpoint) :?>
            <tr><td><?=$endpoint['service_type']?></td><td><?=$endpoint['service_url']?></td><td>
                <? if (!@$fp = fsockopen($endpoint['service_url'],8080, $errno, $errstr, 1)) : ?>
                    <?= Assets::img('icons/16/red/exclaim-circle.png', array('title' => $_("Der Endpoint ist nicht erreichbar."))); ?>
                <? else : ?>
                   <?= Assets::img('icons/16/green/accept.png', array('title' => $_("Der Endpoint ist erreichbar."))); ?>
               <? endif; ?>
            </td></tr>
            <? endforeach; ?>
        </table>

</form>
