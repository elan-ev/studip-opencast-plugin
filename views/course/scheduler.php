<? if (isset($this->flash['error'])): ?>
    <?= MessageBox::error($this->flash['error']) ?>
<? endif ?>
<? if (isset($message)): ?>
    <?= MessageBox::success($message) ?>
<? endif ?>


<?

$aktionen = array();
$aktionen[] = array(
              "icon" => "icons/16/black/upload.png",
              "text" => '<a id="oc_upload_dialog"href="#">' . _("Medien hochladen") . '</a>');

$infobox_content = array(array(
    'kategorie' => _('Hinweise:'),
    'eintrag'   => array(array(
        'icon' => 'icons/16/black/info.png',
        'text' => _("Hier können Sie einzelne Aufzeichnungen für diese Veranstaltung planen.")
    )
)));

$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>


<div class="oc_schedule">
    
    <h2><?//=_('Veranstaltungsaufzeichnungen planen')?></h2>
    <?= $this->render_partial("course/_schedule", array('course_id' => $course_id, 'dates' => $dates)) ?>
    
</div>
