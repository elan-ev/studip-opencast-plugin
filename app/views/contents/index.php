<?php
    URLHelper::removeLinkParam('cid');
?>
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
        REDIRECT_URL: '<?= PluginEngine::getURL('opencastv3', [], 'redirect') ?>',
        AUTH_URL   : '<?= PluginEngine::getURL('opencastv3', [], 'redirect/authenticate') ?>'
    };
    <?= isset($languages) ? "window.OpencastPlugin.STUDIP_LANGUAGES = $languages;" : '' ?>;
    <?= isset($studip_version) ? "window.OpencastPlugin.STUDIP_VERSION = $studip_version;" : '' ?>;
</script>

<?php
    $manifest_path = $this->plugin->getPluginPath() . '/static/.rspack/manifest.json';
    $manifest = is_file($manifest_path) ? json_decode(file_get_contents($manifest_path), true) : [];
    $manifest_entry = $manifest['vueapp/app.js'] ?? [];
    $entry = $manifest_entry['file'] ?? null;
    $css = $manifest_entry['css'] ?? [];
    $asset_base = $this->plugin->getPluginUrl() . '/static/';
?>
<?php foreach ($css as $stylesheet): ?>
<link rel="stylesheet" href="<?= $asset_base . $stylesheet ?>">
<?php endforeach; ?>
<?php if ($entry): ?>
<script type="module" src="<?= $asset_base . $entry ?>"></script>
<?php endif; ?>
