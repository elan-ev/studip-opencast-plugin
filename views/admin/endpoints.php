<?
    use Studip\Button, Studip\LinkButton;

    $infobox_content = array(array(
        'kategorie' => $_('Hinweise:'),
        'eintrag'   => array(array(
            'icon' => 'icons/16/black/info.png',
            'text' => $_("Hier finden Sie eine Auflistung aller gefundenen Services der angebundenen Opencast-Systeme.")
        ))
    ));
    $infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>
<?= $this->render_partial('messages') ?>
<script language="JavaScript">
OC.initAdmin();
</script>

<? foreach ($configs as $id => $config) : ?>
    <?= $this->render_partial("admin/_endpointoverview", [
        'endpoints'      => $endpoints,
        'show_config_id' => $id,
        'config'         => $config
    ]) ?>
    <br><br>
<? endforeach ?>
