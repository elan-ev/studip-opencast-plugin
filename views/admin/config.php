<?
    use Studip\Button, Studip\LinkButton;
    
    Helpbar::get()->addPlainText('',_("Hier kann die Anbindung zum Opencast Matterhorn System verwaltet werden."));
?>

<?= $this->render_partial('messages') ?>

<script language="JavaScript">
OC.initAdmin();
</script>
<?= $this->render_partial("admin/_initial_config", array('info_url' => $info_url,
                              'info_user' =>$info_user,'info_password' => $info_password)) ?>
