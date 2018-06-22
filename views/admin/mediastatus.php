<div id="opencast">
    <h2>
        <?= $_('Festplattenplatz im Tempverzeichnis') ?>
    </h2>

    <?= $memory_space['readable']['used'] ?> / <?= $memory_space['readable']['total'] ?><br>
    <progress value="<?= $memory_space['bytes']['used'] ?>" max="<?= $memory_space['bytes']['total'] ?>" data-label="test"></progress>

    <br><br>


    <? /*
    <h3>
        <?= $_('Hochgeladene Episoden') ?>
    </h3>

    <details>
        <pre><?= print_r($uploaded_episodes) ?></pre>
    </details>
    */ ?>

    <? /*
    <h3>
        <?= $_('Fehlgeschlagene Uploads') ?>
    </h3>

    <details>
        <pre><?= print_r($uploading_episodes) ?></pre>
    </details>
    */ ?>   

    <?= $this->render_partial('admin/_job.php',  [
        'caption' => $_('Nicht abgeschlossene / abgebrochene Uploads'),
        'jobs'    => $upload_jobs['unfinished']
    ]) ?>



    <?= $this->render_partial('admin/_job.php',  [
        'caption' => $_('Erfolgreiche Uploads'),
        'jobs'    => $upload_jobs['successful']
    ]) ?>
</div>
