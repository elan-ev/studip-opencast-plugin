<?php
foreach (['presenter' => 'ReferentIn', 'presentation' => 'Bildschirm', 'audio' => 'Audio', 'supplemental' => 'Materialien'] as $type => $button_text) :?>
    <? $download_type = $type . '_download' ?>
    <? if ($episode[$download_type]) : ?>
        <div>
            <h2><?= _(htmlReady($button_text)) ?></h2>
            <? $download_info = array_reverse($episode[$download_type], true) ?>
            <? foreach ($download_info as $quality => $content) : ?>
                <?= Studip\LinkButton::create(
                    $content['info'] . '  (' . CourseController::nice_size_text($quality) . ')',
                    URLHelper::getURL($content['url']),
                    ['target' => '_blank', 'class' => 'download ' . $type]
                ); ?>
            <? endforeach ?>
        </div>
    <? endif ?>
<? endforeach ?>
