<?
Helpbar::get()->addPlainText(
    '',
    $_('Hier finden Sie eine Auflistung aller gefundenen Services der angebundenen Opencast-Systeme.')
) ?>
<?= $this->render_partial('messages') ?>
<script language="JavaScript">
    OC.initAdmin();
</script>

<? foreach ($configs as $id => $config) : ?>
    <?= $this->render_partial('admin/_endpointoverview', [
        'endpoints'      => $endpoints,
        'show_config_id' => $id,
        'config'         => $config
    ]) ?>
    <br><br>
<? endforeach ?>
