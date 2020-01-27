<? use Studip\Button, Studip\LinkButton; ?>

<? if(!empty($dates)) :?>
<form action="<?= PluginEngine::getLink('opencast/course/bulkschedule/') ?>" method=post>
    <input type="hidden" name="semester_filter" value="<?= $semester_filter ?>">
<table class="default">
    <colgroup>
        <col width="2%">
        <col width="30%">
        <col width="30%">
        <col width="30%">
        <col width="4%">
        <col width="4%">
    </colgroup>
    <tr>
        <th></th>
        <th><?= $_('Termin') ?></th>

        <? if (Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE) : ?>
        <th><?= $_('Aufzeichnungszeitraum') ?></th>
        <? endif ?>

        <th><?= $_('Titel') ?></th>
        <th><?= $_('Status') ?></th>
        <th><?= $_('Aktionen') ?></th>
    </tr>

    <? foreach($dates as $d) : ?>
    <tr>
        <? $date      = new SingleDate($d['termin_id']); ?>
        <? $resource  = $date->getResourceID(); ?>
        <? $scheduled = reset(OCModel::checkScheduled($course_id, $resource, $date->termin_id)) ?>
        <td>
            <? if (isset($resource) && OCModel::checkResource($resource) && (date($d['date']) > time())) :?>
                <input name="dates[<?= $date->termin_id ?>]" type="checkbox" value="<?= $resource ?>"></input>
            <? else: ?>
                <input type="checkbox" disabled></input>
            <? endif;?>
        </td>

        <td>
            <?= $date->getDatesHTML() ?>
        </td>

        <? if (Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE) : ?>
            <? if ($scheduled && date($d['date']) > time()) : ?>
            <td class="oc-schedule-slider"
                data-range-start="<?= (date('G', $date->date) * 60 + date('i', $date->date)) ?>"
                data-range-end="<?= (date('G', $date->end_time) * 60 + date('i', $date->end_time)) ?>"
                data-start="<?= (date('G', $scheduled['start']) * 60 + date('i', $scheduled['start'])) ?>"
                data-end="<?= (date('G', $scheduled['end']) * 60 + date('i', $scheduled['end'])) ?>"
                data-event_id="<?= $scheduled['event_id'] ?>"
            >

            </td>
            <? else : ?>
            <td>
                <span style="color: lightgray">
                    <?= $_('keine Aufzeichnung geplant') ?>
                </span>
            </td>
            <? endif ?>
        <? endif ?>

        <? $issues = $date->getIssueIDs(); ?>
        <? if(is_array($issues)) : ?>
            <? if(sizeof($issues) > 1) :?>
                <? $titles = array(); ?>
                <? foreach($issues as $is) : ?>
                    <? $issue = new Issue(array('issue_id' => $is));?>
                    <? $topic = true; ?>
                    <? $titles[] = my_substr($issue->getTitle(), 0 ,80 );?>
                <? endforeach; ?>
            <td> <?= $_("Themen: ") . my_substr(implode(', ', $titles), 0 ,80 ) ?></td>
            <? else : ?>
            <? foreach($issues as $is) : ?>
                <? $issue = new Issue(array('issue_id' => $is));?>
                <? $topic = true; ?>
                <td> <?= my_substr($issue->getTitle(), 0 ,80 ) ?></td>
            <? endforeach; ?>
            <? endif; ?>
        <? else: ?>
        <? $topic = false; ?>
        <td> <?=$_("Kein Titel eingetragen")?></td>
        <? endif; ?>
        <td>
            <? if (isset($resource) && OCModel::checkResource($resource)) : ?>
            <? if ($scheduled) : ?>
                    <?= new Icon('video', 'info', array(
                        'title' => $_("Aufzeichnung ist bereits geplant.")
                    )) ?>
                <?  else : ?>
                    <? if(date($d['date']) > time()) :?>
                        <?= new Icon('date', 'info', array(
                                'title' => $_("Aufzeichnung ist noch nicht geplant")
                            )) ?>
                    <? else :?>
                        <?= new Icon('exclaim-circle', 'info', array(
                                'title' =>  $_("Dieses Datum liegt in der Vergangenheit. Sie können keine Aufzeichnung planen.")
                            )) ?>
                    <? endif;?>
                <? endif; ?>
            <? else : ?>
                <?= new Icon('exclaim-circle', 'attention', array(
                        'title' =>  $_("Es wurde bislang kein Raum mit Aufzeichnungstechnik gebucht")
                    )) ?>
            <? endif; ?>
        </td>

        <td>
            <? $resource = $date->getResourceID(); ?>
            <? if (isset($resource) && OCModel::checkResource($resource)) : ?>
                <? if ($scheduled && (int)date($d['date']) > time()) :?>
                    <a href="<?=PluginEngine::getLink('opencast/course/update/'.$resource .'/'. $date->termin_id )?>">
                        <?= new Icon('refresh' ,'clickable', array(
                            'title' =>  $_("Aufzeichnung ist bereits geplant. Sie können die Aufzeichnung stornieren oder entsprechende Metadaten aktualisieren.")
                        )) ?>
                    </a>
                    <a href="<?=PluginEngine::getLink('opencast/course/unschedule/'.$resource .'/'. $date->termin_id )?>">
                        <?= new Icon('trash' ,'clickable', array(
                            'title' =>  $_("Aufzeichnung ist bereits geplant. Klicken Sie hier um die Planung zu aufzuheben.")
                        )) ?>
                    </a>
                <?  else : ?>
                    <? if(date($d['date']) > time()) :?>
                        <a href="<?=PluginEngine::getLink('opencast/course/schedule/'.$resource .'/'. $date->termin_id )?>">
                            <?= new Icon('video', 'clickable', array(
                                'title' =>  $_("Aufzeichnung planen.")
                            )) ?>
                        </a>
                    <? else :?>
                        <?= new Icon('video', 'inactive', array(
                            'title' =>  $_("Dieses Datum liegt in der Vergangenheit. Sie können keine Aufzeichnung planen.")
                        )) ?>
                    <? endif;?>
                <? endif; ?>
            <? else : ?>
                <?= new Icon('video', 'inactive', array(
                    'title' =>  $_("Es wurde bislang kein Raum mit Aufzeichnungstechnik gebucht.")
                )) ?>
            <? endif; ?>
        </td>
    </tr>
    <? endforeach; ?>
    <tfoot style="border-top: 1px solid #1e3e70; background-color: #e7ebf1;">
    <tr>
        <td class="thead"><input type="checkbox" data-proxyfor="[name^=dates]:checkbox" id="checkall"></td>
        <td class="thead">
            <select name="action">
                <option value="" disabled selected><?=$_("Bitte wählen Sie eine Aktion.")?></option>
                <option value="create"><?=$_("Aufzeichnungen planen")?></option>
                <option value="update"><?=$_("Aufzeichnungen aktualisieren")?></option>
                <option value="delete"><?=$_("Aufzeichnungen stornieren")?></option>
            </select>
        </td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    </tfoot>
</table>

<div>
            <?= Button::createAccept($_('Übernehmen'), array('title' => $_("Änderungen übernehmen"))); ?>
            <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/course/scheduler')); ?>
</div>
</form>
<? else: ?>
    <?= MessageBox::info($_('Es gibt keine passenden Termine'));?>
<? endif;?>

<? if (Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE) : ?>
<script type="text/javascript">
$(function() {
    $(".oc-schedule-slider").each(function () {
        // initialize data and dom objects for range slider
        let $event_id = $(this).attr('data-event_id');
        let $start    = parseInt($(this).attr('data-start'));
        let $end      = parseInt($(this).attr('data-end'));
        let $rstart    = parseInt($(this).attr('data-range-start'));
        let $rend      = parseInt($(this).attr('data-range-end'));
        let $slider_text  = $('<div class="slider-text"></div>');
        let $slider_range = $('<div class="slider-range"></div>');
        let $slide_start  = $('<input type="hidden" name="start[' + $event_id + '][start]">');
        let $slide_end    = $('<input type="hidden" name="start[' + $event_id + '][start]">');

        // add ui elements and input data to DOM
        $(this).append($slider_text);
        $(this).append($slider_range);
        $(this).append($slide_start);
        $(this).append($slide_end);

        $slider_text.html(
            Math.floor($start / 60).toString().padStart(2, '0')
            + ':' + ($start - Math.floor($start / 60) * 60).toString().padStart(2, '0')
            + ' - ' + Math.floor($end / 60).toString().padStart(2, '0')
            + ':' +  ($end - Math.floor($end / 60) * 60).toString().padStart(2, '0')
        );

        // mount the slider
        $slider_range.slider({
            range: true,
            min: $rstart,
            max: $rend,
            step: 5,
            values: [$start, $end],
            slide: function(event, ui) {
                $slide_start.val(ui.values[0]);
                $slide_end.val(ui.values[1]);

                var start_hours  = Math.floor(ui.values[0] / 60);
                var start_minutes = ui.values[0] - (start_hours * 60);

                var end_hours  = Math.floor(ui.values[1] / 60);
                var end_minutes = ui.values[1] - (end_hours * 60);

                // show some text for the User
                $slider_text.html(
                    start_hours.toString().padStart(2, '0')
                    + ':' + start_minutes.toString().padStart(2, '0')
                    + ' - ' + end_hours.toString().padStart(2, '0')
                    + ':' + end_minutes.toString().padStart(2, '0')
                );
            },
            stop: function(event, ui) {
                // store values when user is done with sliding
                $.post('<?= $controller->url_for('course/schedule_update') ?>', {
                    event_id: $event_id,
                    start: ui.values[0],
                    end: ui.values[1]
                });
            }
        });
    });
});
</script>
<? endif ?>
