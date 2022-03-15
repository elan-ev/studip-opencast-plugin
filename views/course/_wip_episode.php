<? if (!empty($instances)) : ?>

    <form class="default collapsable" action="">
        <fieldset class="collapsed">
            <legend>
                <?= sizeof($instances) ?> <?= $_('Aufzeichnungen in Bearbeitung') ?>
            </legend>

            <div id="episodes">
                <ul class="oce_list list">
                    <? foreach ($instances as $episode) : ?>
                        <li class="uploaded oce_item">
                            <div class="oce_wip" id="<?= $workflow_id ?>" title="<?= $_('Aktueller Arbeitsschritt:')
                            . ' ' . $episode->title ?>"
                            >
                                <div class="oce_wip_preview">
                                    <img src="<?= $plugin->getPluginURL() . '/images/opencast-black.svg' ?>">
                                </div>

                                <div style="clear: both;"></div>
                            </div>
                            <div class="oce_metadatacontainer">
                                <h2 class="oce_list_title">
                                    <?= htmlready($episode->mediapackage->title) ?>
                                    <?= tooltipIcon($_('Aktueller Arbeitsschritt:') . ' ' . $episode->title) ?>
                                </h2>

                                <ul class="oce_contentlist">
                                    <li>
                                        <?= $_('Video wird verarbeitet...') ?>
                                    </li>
                                    <li>
                                        <?= $_('Ersteller/in:') ?>
                                        <?= $episode->mediapackage->creators->creator ?>
                                    </li>
                                    <li class="oce_list_date">
                                        <?= $_('Aufnahemezeitpunkt:') ?>
                                        <?= date('d.m.Y H:i', strtotime($episode->mediapackage->start)) ?>
                                        <?= $_("Uhr") ?>
                                    </li>
                                    <li>
                                        <?= $_('Beschreibung:') ?>
                                        <?= $episode->mediapackage->description ? htmlReady($episode->mediapackage->description) : 'Keine Beschreibung vorhanden' ?>
                                    </li>
                                    <li>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <? endforeach; ?>
                </ul>
            </div>
        </fieldset>
    </form>
<? endif; ?>
