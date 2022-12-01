<? use Studip\Button, Studip\LinkButton; ?>

<?= MessageBox::info($_('Ihr Suchbegriff muss mindestens 3 Zeichen lang sein und es werden maximal 100 Treffer angezeigt.')) ?>

<form action="<?= $controller->url_for('course/edit/' . $course_id) ?>"
      method=post id="select-series" class="default"
      data-unconnected="<?= (empty($connectedSeries) ? 1 : 'false'); ?>">
    <fieldset>
        <legend>
            <?= $_('Serie mit Veranstaltung verknüpfen') ?>
        </legend>

        <label>
            Suche
            <input type="text"
                id="search_term"
                placeholder="<?= $_('Serientitel oder Beschreibung') ?>"
                onKeyUp="runSearch()">
        </label>

        <label>
            Gefunde Serien
            <select name="series" required
                    id="series-select"
                    data-placeholder="<?= $_('Wählen Sie eine Series aus.') ?>"
            >

                <? foreach ($configs as $id => $config): ?>
                    <optgroup label="<?= $_(sprintf('%s. Opencast-System', $id)) ?>" id="oc_server_<?= $id ?>">
                    </optgroup>
                <? endforeach ?>
            </select>
        </label>

    </fieldset>


    <footer data-dialog-button>
        <?= Button::createAccept($_('Übernehmen'), ['title' => $_('Änderungen übernehmen'), 'class' => 'oc-debounce']); ?>
        <?= LinkButton::createCancel($_('Abbrechen'), $controller->url_for('course/index')); ?>
    </footer>
</form>

<script type="text/javascript">
    function runSearch()
    {
        let search_term = $('#search_term').val();
        fetch(STUDIP.URLHelper.getURL('plugins.php/opencast/ajax/search_series/?search_term=' + search_term))
            .then((response) => response.json())
            .then((data) => {
                for (let config_id in data) {
                    $('#oc_server_' + config_id).replaceOptions(data[config_id], config_id);
                }

                // console.log(data);
            });
    }

    (function($, window) {
        $.fn.replaceOptions = function(options, config_id) {
            var self, $option;

            this.empty();
            self = this;

            $.each(options, function(index, option) {
                $option = $("<option></option>")
                    .attr("value", '{"config_id":"' + config_id + '", "series_id":"' + index + '"}')
                    .text(option);
                self.append($option);
            });
        };
    })(jQuery, window);
</script>
