<?
$sidebar = Sidebar::get();
$actions = new ActionsWidget();
$actions->addLink(
    $_("Episoden aller Series abgleichen"),
    PluginEngine::getLink('opencast/admin/refresh_episodes/' . get_ticket()),
    new Icon('refresh', 'clickable')
);

$actions->addLink(
    $_("Zwischenspeicher leeren"),
    PluginEngine::getLink('opencast/admin/refresh_cache/' . get_ticket()),
    new Icon('refresh', 'clickable')
);
$sidebar->addWidget($actions);

Helpbar::get()->addPlainText('', $_('Hier wird die Anbindung zum Opencast System verwaltet.
Geben Sie die Daten ihres Opencast-Systems ein, um Aufzeichnungen in Ihren Veranstaltungen bereitstellen zu können.
Optional haben Sie die Möglichkeit, ein zweites Opencast-System im Nur-Lesen-Modus anzubinden.')
);
?>

<?= $this->render_partial('messages') ?>

<script language="JavaScript">
    OC.initAdmin();
</script>

<div id="opencast">
    <?= $this->render_partial('admin/_initial_config') ?>
</div>
