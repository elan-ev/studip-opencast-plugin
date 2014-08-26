<? if (empty($this->connectedSeries)) : ?>
    <?= MessageBox::info(sprintf(_("Sie haben noch keine Series aus Opencast mit dieser Veranstaltung verknüpft.
                            Bitte verknüpfen eine bereits vorhandene Series oder %s erstellen Sie eine neue.%s"), '<a href="' . PluginEngine::getLink('opencast/course/create_series/') . '">', '</a>'))
    ?>
<?php endif; ?>


<?= $this->render_partial("course/_connectedSeries", array('course_id' => $course_id, 'connectedSeries' => $connectedSeries, 'unonnectedSeries' => $unonnectedSeries, 'series_client' => $series_client)) ?>
