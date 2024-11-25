<div class="container" id="opencast">
    <h1 class="display-1 text-center">Starte Anwendung&hellip;</h1>
</div>

<script type="text/javascript">
    window.OpencastPlugin = {
        API_URL    : '<?= PluginEngine::getURL('opencastv3', [], 'api', true) ?>',
        IMAGE      : '<?= Assets::url('images/icons/') ?>',
        ICON_URL   : '<?= Assets::url('images/icons/') ?>',
        ASSETS_URL : '<?= Assets::url('') ?>',
        PLUGIN_ASSET_URL : '<?= $plugin->getAssetsUrl() ?>',
        ROUTE      : 'videos',
        REDIRECT_URL: '<?= PluginEngine::getURL('opencastv3', [], 'redirect/perform') ?>',
        AUTH_URL   : '<?= PluginEngine::getURL('opencastv3', [], 'redirect/authenticate') ?>'
    };
    <?= isset($languages) ? "window.OpencastPlugin.STUDIP_LANGUAGES = $languages;" : '' ?>;
    <?= isset($studip_version) ? "window.OpencastPlugin.STUDIP_VERSION = $studip_version;" : '' ?>;
</script>

<!-- load bundles -->
<% for(var i = 0; i < htmlWebpackPlugin.tags.headTags.length; i++) { %>
<? PageLayout::addScript($this->plugin->getPluginUrl() . '/static<%= htmlWebpackPlugin.tags.headTags[i].attributes.src %>'); ?>
<% } %>
<!-- END load bundles -->
