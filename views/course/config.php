<? use Studip\Button, Studip\LinkButton; ?>

<form
    action="<?= PluginEngine::getLink('opencast/course/edit/' . $course_id) ?>"
    method=post id="select-series" class="default"
    data-unconnected="<?= (empty($connectedSeries) ? 1 : 'false');?>"
>
    <fieldset>
        <legend>
            <?= $_('Serie mit Veranstaltung verknüpfen') ?>
        </legend>

        <? if (!empty($all_series)) : ?>
            <label>
                <select name="series"
                    class="chosen-select"
                    data-placeholder="<?=$_('Wählen Sie eine Series aus.')?>"
                >

                <? foreach ($configs as $id => $config): ?>
                <optgroup label="<?= $_(sprintf('%s. Opencast-System', $id)) ?>">
                    <? foreach ($all_series[$id] as $serie) : ?>
                        <?// if (isset($serie['identifier'])) : ?>
                            <option value='{"config_id":"<?= $id ?>", "series_id":"<?= $serie->id ?>"}'
                                    class="nested-item">
                                <?= studip_utf8decode($serie->dcTitle)?>
                            </option>
                        <?//endif;?>
                    <?endforeach;?>
                </optgroup>
                <? endforeach ?>
                </select>
            </label>
        <? endif;?>
    </fieldset>



    <footer data-dialog-button>
        <?= Button::createAccept($_('Übernehmen'), array('title' => $_("Änderungen übernehmen"))); ?>
        <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/course/index')); ?>
    </footer>
</form>

<script type="text/javascript">
    jQuery(".chosen-select").chosen({
        disable_search_threshold: 10,
        max_selected_options: 1,
        no_results_text: "Oops, nothing found!",
        width: "350px"
    });
</script>
