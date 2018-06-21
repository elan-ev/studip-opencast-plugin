<?php
/**
 * @author              Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright       (c) Authors
 * @version             1.0 (11:24)
 */
?>

<h3>used space on system</h3>
<?= $memory_space['readable']['used'] ?> of <?= $memory_space['readable']['total'] ?> :
<progress value="<?= $memory_space['bytes']['used'] ?>" max="<?= $memory_space['bytes']['total'] ?>" data-label="test"></progress>

<h3>opencast successful upload jobs</h3>
<details>
    <pre><?= print_r($upload_jobs['successful']) ?></pre>
</details>


<h3>uploaded</h3>
<details>
    <pre><?= print_r($uploaded_episodes) ?></pre>
</details>


<h3>opencast unfinished upload jobs</h3>
<details>
    <pre><?= print_r($upload_jobs['unfinished']) ?></pre>
</details>


<h3>currently ingesting / possible failed</h3>
<details>
    <pre><?= print_r($uploading_episodes) ?></pre>
</details>

