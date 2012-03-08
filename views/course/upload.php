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
        'text' => _("Hier können Sie AV-Medien direkt in das angebunde OpenCast Matterhorn laden.")
    ))
));
$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>

<section>
    Gedöns
</section>