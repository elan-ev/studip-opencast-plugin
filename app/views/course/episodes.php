<div class="container" id="app-episodes">
    <h1 class="display-1 text-center">Starte Anwendung&hellip;</h1>
</div>

<script type="text/javascript">
    let API_URL  = '<?= PluginEngine::getURL('opencast', [], 'api', true) ?>';
    let CID      = '<?= $cid ?>';
    let ICON_URL = '<?= Assets::url('images/icons/') ?>';
    let ASSETS_URL = '<?= Assets::url('') ?>';
    let PLUGIN_ASSET_URL =  '<?= $plugin->getAssetsUrl() ?>';
</script>

<!-- load bundles -->

<? PageLayout::addScript($this->plugin->getPluginUrl() . '/static/main.40e857e8d5c17de989c1.js'); ?>

<!-- END load bundles -->
