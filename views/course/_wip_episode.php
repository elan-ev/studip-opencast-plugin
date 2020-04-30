<? if (!empty($wip_episodes)) : ?>
<h1><?= $_('Aufzeichnungen in Bearbeitung') ?></h1>

<div class="oc_flex">
    <div id="episodes" class="oc_flexitem oc_flexepisodelist">
        <ul class="oce_list list">
            <? foreach ($wip_episodes as $episode) : ?>
                <li class="uploaded oce_item">
                    <? if ($episode->processing_state == 'FAILED') : ?>
                        <div class="oce_wip">
                            <div class="oce_wip_preview">
                                <img src="<?= $plugin->getPluginURL() . '/images/opencast-red.svg' ?>">
                            </div>
                        </div>
                        <div class="oce_metadatacontainer oce_failedstate">
                            <h2 class="oce_list_title">
                                <?= htmlready($state->mediapackage->title) ?>
                            </h2>

                            <div>
                                <?= $_("Videoverarbeitung fehlgeschlagen") ?>
                            </div>

                            <?= Studip\LinkButton::create($_('Daten vom Server entfernen'), PluginEngine::getLink('opencast/course/remove_failed/' . $state->id)); ?>
                        </div>
                    <? elseif ($episode->processing_state == 'RUNNING'): ?>
                        <div class="oce_wip" id="<?= $workflow_id ?>">
                            <div class="oce_wip_preview">
                                <img src="<?= $plugin->getPluginURL() . '/images/opencast-black.svg' ?>">
                            </div>

                            <div style="clear: both;"></div>
                        </div>
                        <div class="oce_metadatacontainer">
                            <h2 class="oce_list_title">
                                <?= htmlready($episode->title) ?>
                            </h2>

                            <ul class="oce_contetlist">
                                <li>
                                    <?= $_('Video wird verarbeitet...') ?>
                                </li>
                                <li class="oce_list_date">
                                    <?= $_('Hochgeladen am:') ?>
                                    <?= date("d.m.Y H:i", strtotime($episode->created)) ?>
                                    <?= $_("Uhr") ?>
                                </li>
                                <li>
                                    <?= $_('Beschreibung:') ?>
                                    <?= $item['description'] ? htmlReady($episode->description) : 'Keine Beschreibung vorhanden' ?>
                                </li>
                            </ul>
                        </div>
                    <? endif; ?>
                </li>
            <? endforeach; ?>
        </ul>
    </div>
</div>
<? endif; ?>
