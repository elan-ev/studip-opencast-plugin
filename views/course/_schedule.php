<? use Studip\Button, Studip\LinkButton; ?>

<? if(!empty($dates)) :?>
<form action="<?= PluginEngine::getLink('opencast/course/bulkschedule/') ?>" method=post>
<table class="default">
    <tr>
        <th></th>
        <th><?= $_('Termin') ?></th>
        <th><?= $_('Titel') ?></th>
        <th><?= $_('Status') ?></th>
        <th><?= $_('Aktionen') ?></th>
    </tr>

    <? foreach($dates as $d) : ?>
    <tr>
        <? $date = new SingleDate($d['termin_id']); ?>
        <? $resource = $date->getResourceID(); ?>
        <td>
            <? if(isset($resource) && OCModel::checkResource($resource) && (date($d['date']) > time())) :?>
                <input name="dates[<?=$date->termin_id?>]" type="checkbox" value="<?=$resource?>"></input>
            <? else: ?>
                <input type="checkbox" disabled></input>
            <? endif;?>
        </td>
        <td> <?=$date->getDatesHTML()?> </td>
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
            <? if(isset($resource) && OCModel::checkResource($resource)) :?>
            <? if(OCModel::checkScheduled($course_id, $resource, $date->termin_id)) :?>
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
            <? if(isset($resource) && OCModel::checkResource($resource)) :?>
                <? if(OCModel::checkScheduled($course_id, $resource, $date->termin_id) && (int)date($d['date']) > time()) :?>
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
