<div class="container" id="opencast">
    <h1 class="display-1 text-center">Starte Anwendung&hellip;</h1>
</div>

<script type="text/javascript">
    window.OpencastPlugin = {
        API_URL    : '<?= PluginEngine::getURL('opencastv3', [], 'api', true) ?>',
        CID        : '<?= $course_id ?>',
        ICON_URL   : '<?= Assets::url('images/icons/') ?>',
        ASSETS_URL : '<?= Assets::url('') ?>',
        PLUGIN_ASSET_URL : '<?= $plugin->getAssetsUrl() ?>',
        ROUTE      : 'course',
        REDIRECT_URL: '<?= PluginEngine::getURL('opencastv3', [], 'redirect', true) ?>',
        AUTH_URL: '<?= PluginEngine::getURL('opencastv3', [], 'redirect/authenticate', true) ?>'
    };
    <?= isset($languages) ? "window.OpencastPlugin.STUDIP_LANGUAGES = $languages;" : '' ?>;
    <?= isset($studip_version) ? "window.OpencastPlugin.STUDIP_VERSION = $studip_version;" : '' ?>;
</script>

<?php
    $manifest_path = $this->plugin->getPluginPath() . '/static/.vite/manifest.json';
    $manifest = is_file($manifest_path) ? json_decode(file_get_contents($manifest_path), true) : [];
    $entry = $manifest['vueapp/app.js']['file'] ?? null;
    $asset_base = $this->plugin->getPluginUrl() . '/static/';
?>
<?php if ($entry): ?>
<script type="module" src="<?= $asset_base . $entry ?>"></script>
<?php endif; ?>
