<?= $this->render_partial('messages') ?>

<?
    Helpbar::get()->addPlainText('',_("Hier können Sie einzelne Aufzeichnungen für diese Veranstaltung planen."));
?>


<div class="oc_schedule">
    
    <h2><?//=_('Veranstaltungsaufzeichnungen planen')?></h2>
    <?= $this->render_partial("course/_schedule", array('course_id' => $course_id, 'dates' => $dates)) ?>
    
</div>

<script language="JavaScript">
    OC.initScheduler();
</script>