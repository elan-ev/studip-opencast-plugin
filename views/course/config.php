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
                    id="series-select"
                    data-placeholder="<?=$_('Wählen Sie eine Series aus.')?>"
                    style="max-width: 500px"
                >

                <? foreach ($configs as $id => $config): ?>
                <optgroup label="<?= $_(sprintf('%s. Opencast-System', $id)) ?>">
                    <? foreach ($all_series[$id] as $serie) : ?>
                        <option value='{"config_id":"<?= $id ?>", "series_id":"<?= $serie->id ?>"}'
                                class="nested-item">
                            <?= $serie->dcTitle ?>
                        </option>
                    <?endforeach;?>
                </optgroup>
                <? endforeach ?>
                </select>
            </label>
        <? else: ?>
            <?= MessageBox::info($_('Es wurden in Opencast keine Serien gefunden.')) ?>
        <? endif;?>
    </fieldset>



    <footer data-dialog-button>
        <?= Button::createAccept($_('Übernehmen'), array('title' => $_("Änderungen übernehmen"))); ?>
        <?= LinkButton::createCancel($_('Abbrechen'), PluginEngine::getLink('opencast/course/index')); ?>
    </footer>
</form>

<script type="text/javascript">
    jQuery("#series-select").select2({
        max_selected_options: 1,
        width: "500px",
        dropdownParent: $('#select-series')
    });
</script>
