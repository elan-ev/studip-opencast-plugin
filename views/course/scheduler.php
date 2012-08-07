<? if (isset($message)): ?>
    <?= MessageBox::success($message) ?>
<? endif ?>

<?
$infobox_content = array(array(
    'kategorie' => _('Hinweise:'),
    'eintrag'   => array(array(
        'icon' => 'icons/16/black/info.png',
        'text' => _("Hier können Sie einzelne Aufzeichnungen für diese Veranstaltung planen.")
    ))
));

$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>


<div class="oc_schedule">
    <h3><?=_('Veranstaltungsaufzeichnungen Planen')?>:</h3>
    <!--        <h4>Ablaufplan</h4>
<p>Erst feststellen ob ein Raum mit CA da ist und dann den Ablaufplan posten mit Checkboxes zum Schedulen...</p> -->
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
                <? $topic = true; ?>
                <td> <?= my_substr($issue->getTitle(), 0 ,80 ); ?></td>
                <? endforeach; ?>
            <? else: ?>
            <? $topic = false; ?>
            <td> <?=_("Kein Titel eingetragen")?></td>
            <? endif; ?>
            <td>
                <? $resource = $date->getResourceID(); ?>
                <? if(isset($resource) && OCModel::checkResource($resource)) :?>
                <? if(OCModel::checkScheduled($course_id, $resource, $date->termin_id)) :?>
                    <a href="<?=PluginEngine::getLink('opencast/course/unschedule/'.$resource .'/'. $date->termin_id ) ?>">
                        <?= Assets::img('icons/16/blue/video.png', array('title' => _("Aufzeichnung ist bereits geplant. Klicken Sie hier um die Planung zu aufzuheben"))) ?>
                    </a>
                    <?  else : ?>
                    <? if($topic) :?>
                        <? if(date($d['date']) > time()) :?>
                            <a href="<?=PluginEngine::getLink('opencast/course/schedule/'.$resource .'/'. $date->termin_id ) ?>">
                                <?= Assets::img('icons/16/blue/date.png', array('title' => _("Aufzeichnung planen"))) ?>
                            </a>
                        <? else :?>
                            <?= Assets::img('icons/16/blue/exclaim-circle.png', array('title' =>  _("Dieses Datum liegt in der Vergangenheit. Sie können keine Aufzeichnung planen."))) ?>
                        <? endif;?>
                    <? else :?>
                        <?= Assets::img('icons/16/blue/exclaim-circle.png', array('title' =>  _("Bitte geben Sie ein Thema für diese Veranstaltung an."))) ?>
                        <? endif ;?>
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
                <?= Assets::img('icons/16/red/exclaim-circle.png', array('title' =>  _("Es wurde bislang kein Raum mit Aufzeichnungstechnik gebucht"))) ?>
                <? endif; ?>


            </td>
        </tr>
        <? endforeach; ?>

    </table>
</div>
