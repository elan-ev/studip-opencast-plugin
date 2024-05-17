<?
$config = Opencast\Models\OCConfig::findBySql(1);
foreach ($config as $conf) :
    // iterate over all opencast nodes for this server as well
    $num = 0;
    foreach (Opencast\LTI\LtiHelper::getLtiLinks($conf->id) as $link) : ?>
        <iframe
            height="0" width="0" style="border: 0px"
            src="<?= \PluginEngine::getURL('opencast', [
                'config_id' => $conf->id,
                'cid'       => Context::getId()
            ], 'redirect/authenticate/' . $num, true) ?>">
        </iframe>
        <? $num++ ?>
    <? endforeach ?>
<? endforeach ?>