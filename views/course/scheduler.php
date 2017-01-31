<?= $this->render_partial('messages') ?>

<?
    Helpbar::get()->addPlainText('',_("Hier können Sie einzelne Aufzeichnungen für diese Veranstaltung planen."));
    $sidebar = Sidebar::get();

    /*
     *  We don't need that widget now.
    $widget = new SelectWidget(_('Semesterauswahl'),
        URLHelper::getLink('?cmd=applyFilter'),
        'newFilter');



    foreach ($course_semester as $item) {
        $element = new SelectElement($item['semester_id'], $item['name'], $item['past']);
        $widget->addElement($element);
    }
    $sidebar->addWidget($widget);

    */



?>


<div class="oc_schedule">
    
    <h2><?//=_('Veranstaltungsaufzeichnungen planen')?></h2>
    <?= $this->render_partial("course/_schedule", array('course_id' => $course_id, 'dates' => $dates)) ?>
    
</div>

<script language="JavaScript">
    OC.initScheduler();
</script>