<? if (isset($message)): ?>
  <?= MessageBox::success($message) ?>
<? endif ?>

<?
$infobox_content = array(array(
    'kategorie' => _('Hinweise:'),
    'eintrag'   => array(array(
        'icon' => 'icons/16/black/info.png',
        'text' => _("Hier können die eingebundenen Vorlesungsaufzeichnungen aus dem angebundenen Opencast Matterhorn System verwaltet werden. Sie können Series, die noch keiner Veranstaltung zugeordnet sind der aktuellen Verantstaltung zuordnen. Wenn Sie bestehende Zuordnungen löschen möchten, klichen Sie auf den jeweiligen Mülleimer.")
    ))
));

$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>
<script language="JavaScript">
OC.initAdmin();
</script>
<h3><?=_('Verwaltung der eingebundenen Vorlesungsaufzeichnungen')?></h3>

<? if (empty($this->cseries)) : ?>
    <?= MessageBox::info(_("Sie haben noch keine Series aus Opencast mit dieser Veranstaltung verknüpft. 
                            Bitte erstellen Sie zuerst eine Series oder verknüpfen eine bereits vorhandene Series.")) ?>
    <div id="admin-accordion">
        <h3><?= _("Neue Series Anlegen") ?></h3>
        <?= $this->render_partial("course/_createSeries") ?>
        <h3><?=_('Wählen Sie rechts eine Series aus, die Sie mit der aktuellen Veranstaltung verknüpfen möchten')?>:</h3>
        <?= $this->render_partial("course/_connectedSeries", array('course_id' => $course_id, 'cseries' => $cseries, 'rseries' => $rseries, 'series_client' => $series_client)) ?>
    </div>


<? else : ?>


    <h3><?=_('Veranstaltungsaufzeichnungen Planen')?>:</h3>
    <div class="oc_schedule">
        <h4>Ablaufplan</h4>
        <p>Erst feststellen ob ein Raum mit CA da ist und dann den Ablaufplan posten mit Checkboxes zum Schedulen...</p>
        <table class="default">
            <tr>
                <th>Termin</th>
                <th>Titel</th>
                <th>Aktionen</th>
            </tr>
            
            <? foreach($dates as $d) : ?>
                <tr>
                <? $date = new SingleDate($d['termin_id']); ?>
                <td> <?=$date->getDatesHTML()?> </td>
                <? $issues = $date->getIssueIDs(); ?>
                <? if(is_array($issues)) : ?>
                    <? foreach($issues as $is) : ?>
                        <? $issue = new Issue(array('issue_id' => $is));?>
                        <td> <?= my_substr($issue->getTitle(), 0 ,80 ); ?></td>
                    <? endforeach; ?>
                <? else: ?>
                        <td> <?=_("Kein Titel eingetragen")?></td>
                <? endif; ?>
                <td>
                    <? $resource = $date->getResourceID(); ?>
                    <? if(isset($resource) && OCModel::checkResource($resource)) :?>
                        <? if(OCModel::checkScheduled($course_id, $resource, $date->termin_id)) :?>
                            <a href="<?=PluginEngine::getLink('opencast/course/config/' ) ?>">
                                <?= Assets::img('icons/16/blue/video.png', array('title' => _("Aufzeichnung ist bereits geplant."))) ?>
                            </a>
                        <?  else : ?>
                            <a href="<?=PluginEngine::getLink('opencast/course/schedule/'.$resource .'/'. $date->termin_id ) ?>">
                                <?= Assets::img('icons/16/blue/date.png', array('title' => _("Aufzeichnung planen"))) ?>
                            </a>
                        <? endif; ?>
                    <?
                        
                    ?>
                        <?// $date->getRoom() ?>
                    <? elseif(false) : ?>
                        <?= Assets::img('icons/16/blue/video.png') ?>
                    <?
                        /*  Wenn es eine Aufzeichnung gibt, optionen zum Unsichtbar machen anbieten
                         *  Wenn keine Aufzeichnung aus OC gibt dann ersma nix machen
                         *
                         *
                         */
                    ?>
                    <? else : ?>
                        <?= Assets::img('icons/16/red/exclaim-circle.png', array('title' =>  _("Es wurde bislang kein Raum gebucht"))) ?>
                    <? endif; ?>


                </td>
                </tr>
            <? endforeach; ?>

        </table>
    </div>

<? endif; ?>