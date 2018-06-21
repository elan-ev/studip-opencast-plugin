<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright       (c) Authors
 * @version         1.0 (11:24)
 */
?>

<h3>space on system</h3>
<pre><?= var_dump($memory_space)?></pre>

<h3>opencast successful upload jobs</h3>
<pre><?= var_dump($upload_jobs['successful'])?></pre>

<h3>uploaded</h3>
<pre><?= var_dump($uploaded_episodes)?></pre>

<h3>opencast unfinished upload jobs</h3>
<pre><?= var_dump($upload_jobs['unfinished'])?></pre>

<h3>currently ingesting</h3>
<pre><?= var_dump($uploading_episodes) ?></pre>
