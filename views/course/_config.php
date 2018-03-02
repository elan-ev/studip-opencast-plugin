<? use Studip\Button, Studip\LinkButton; ?>
<div>
    <span class="oc_config_infotext">
        <?=$_('Suchen Sie eine Series im Opencast Matterhorn System, die Sie mit der aktuellen Veranstaltung verknüpfen möchten.')?>
    </span>
    <form action="<?= PluginEngine::getLink('opencast/course/edit/' . $course_id) ?>"
      method=post id="select-series" data-unconnected="<?= (empty($connectedSeries) ? 1 : 'false');?>">
        <div style="text-align: center;">
            <div style="text-align:left; padding-left:25%;">
            <? if (!empty($unconnectedSeries)) : ?>
            <select class="series_select chosen-select" multiple name="series[]"  data-placeholder="<?=$_('Wählen Sie eine Series aus.')?>">
            <? foreach ($unconnectedSeries as $serie) : ?>
                <?// if (isset($serie['identifier'])) : ?>
                    <option value="<?= $serie['identifier'] ?>"><?= studip_utf8decode($serie['title'])?></option>
                <?//endif;?>
            <?endforeach;?>
            </select>
            <?endif;?>
            </div>
        </div>
        <div style="padding-top:2em;clear:both" class="form_submit change_series">
            <?= Button::createAccept($_('Übernehmen'), array('title' => $_("Änderungen übernehmen"))); ?>
            <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/course/index')); ?>
        </div>
    </form>
</div>
