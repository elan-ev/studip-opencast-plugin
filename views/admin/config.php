<?
    use Studip\Button, Studip\LinkButton;
    $sidebar = Sidebar::get();
    $actions = new ActionsWidget();
    $actions->addLink($_("Episoden aller Series abgleichen"), PluginEngine::getLink ('opencast/admin/refresh_episodes/' . get_ticket()), new Icon('refresh', 'clickable'));
    $sidebar->addWidget($actions);


    Helpbar::get()->addPlainText('',$_("Hier kann die Anbindung zum Opencast System verwaltet werden."));
?>

<?= $this->render_partial('messages') ?>

<script language="JavaScript">
OC.initAdmin();
</script>
<?= $this->render_partial("admin/_initial_config", array('info_url' => $info_url,
                              'info_user' =>$info_user,'info_password' => $info_password)) ?>
