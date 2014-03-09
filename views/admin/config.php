<?
    use Studip\Button, Studip\LinkButton;

    if ($success = $flash['success']) {
        echo MessageBox::success($success);
    }
    if ($error = $flash['error']) {
        echo MessageBox::error($error);
    }
    if ($flash['question']) {
        echo $flash['question'];
    }


    $infobox_content = array(array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(array(
            'icon' => 'icons/16/black/info.png',
            'text' => _("Hier kann die Anbindung zum Opencast Matterhorn System verwaltet werden.")
        ))
    ));
    $infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>
<script language="JavaScript">
OC.initAdmin();
</script>
<?= $this->render_partial("admin/_initial_config", array('info_url' => $info_url,
                              'info_user' =>$info_user,'info_password' => $info_password)) ?>
