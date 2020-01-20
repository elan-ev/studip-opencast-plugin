<?= $this->render_partial('messages') ?>
<?
$widget = new SelectWidget(_('Semesterfilter'),
    $controller->url_for('course/scheduler'),
    'semester_filter'
);
foreach ($this->selectable_semesters as $item) {
    $element = new SelectElement($item['semester_id'],
                                 $item['name'],
                                 $item['semester_id'] == $this->semester_filter);
    $widget->addElement($element);
}

Sidebar::Get()->addWidget($widget);

Helpbar::get()->addPlainText('', $_("Hier können Sie einzelne Aufzeichnungen für diese Veranstaltung planen."));

?>
<div class="oc_schedule">
    <?= $this->render_partial("course/_schedule", array('course_id' => $course_id, 'dates' => $dates)) ?>
</div>

<script language="JavaScript">
    OC.initScheduler();
</script>
