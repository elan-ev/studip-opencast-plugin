<? if (!empty($wip_episodes)) : ?>
    <section class="contentbox">
        <header>
            <h1><?= $_('Aufzeichnungen in Bearbeitung') ?></h1>
        </header>
        <div class="oc_flex">
            <div id="episodes" class="oc_flexitem oc_flexepisodelist">
                <ul class="oce_list list">
                    <? foreach ($wip_episodes as $episode) : ?>
                        <li class="uploaded oce_item">
                            <div class="oce_wip" id="<?= $workflow_id ?>" title="<?= $_('Aktueller Arbeitsschritt:')
                            . ' ' . $instances[$episode->identifier]->operations->operation->description ?>"
                            >
                                <div class="oce_wip_preview">
                                    <img src="<?= $plugin->getPluginURL() . '/images/opencast-black.svg' ?>">
                                </div>

                                <div style="clear: both;"></div>
                            </div>
                            <div class="oce_metadatacontainer">
                                <h2 class="oce_list_title">
                                    <?= htmlready($episode->title) ?>
                                    <?= tooltipIcon($_('Aktueller Arbeitsschritt:') . ' ' . $instances[$episode->identifier]->operations->operation->description) ?>
                                </h2>

                                <ul class="oce_contentlist">
                                    <li>
                                        <?= $_('Video wird verarbeitet...') ?>
                                    </li>
                                    <li class="oce_list_date">
                                        <?= $_('Hochgeladen am:') ?>
                                        <?= date('d.m.Y H:i', strtotime($episode->created)) ?>
                                        <?= $_("Uhr") ?>
                                    </li>
                                    <li>
                                        <?= $_('Beschreibung:') ?>
                                        <?= $episode->description ? htmlReady($episode->description) : 'Keine Beschreibung vorhanden' ?>
                                    </li>
                                    <li>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <? endforeach; ?>
                </ul>
            </div>
        </div>
    </section>
<? endif; ?>
