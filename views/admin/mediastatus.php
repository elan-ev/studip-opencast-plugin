<div id="opencast">
    <h1>
        <?= $_('Festplattenplatz im Tempverzeichnis') ?>
    </h1>

    <?= sprintf($_('Belegt: %s von %s'),
        $memory_space['readable']['used'],
        $memory_space['readable']['total']
    ) ?>
    <br>

    <progress value="<?= $memory_space['bytes']['used'] ?>" max="<?= $memory_space['bytes']['total'] ?>" data-label="test"></progress>

    <br><br>

    <?= $this->render_partial('admin/_job.php',  [
        'caption' => $_('Nicht abgeschlossene / abgebrochene Uploads'),
        'jobs'    => $upload_jobs['unfinished']
    ]) ?>

    <?= $this->render_partial('admin/_job.php',  [
        'caption' => $_('Erfolgreiche Uploads'),
        'jobs'    => $upload_jobs['successful']
    ]) ?>
</div>
