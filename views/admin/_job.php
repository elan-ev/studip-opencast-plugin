<table class="default">
    <colgroup>
        <col width="5%">
        <col width="30%">
        <col width="30%">
        <col width="15%">
        <col width="20%">
    </colgroup>

    <caption><?= $caption ?></caption>
    <thead>
        <tr>
            <th>#</th>
            <th>
                <?= _('Veranstaltung') ?>
            </th>
            <th>
                <?= $_('Medientitel') ?>
            </th>
            <th>
                <?= $_('Datum des Uploads') ?>
            </th>

            <th>
            </th>
        </tr>
    </thead>

    <tbody>
        <? foreach ($jobs as $job) : ?>
        <tr>
            <td>
                <a href="#" onClick="jQuery('<div><pre><?=  preg_replace("/\n/m", '\n', print_r($job->data(), 1)) ?></pre></div>').dialog({ 'title': 'Details', 'width': '80%'})">
                    <?= Icon::create('info', 'clickable') ?>
                </a>
            </td>

            <td>
                <? try { ?>
                <a href="<?= URLHelper::getLink('plugins.php/opencast/course', ['cid' => $job->data()['id_list']['course']]) ?>" target="_blank">
                    <?= Seminar::getInstance($job->data()['id_list']['course'])->Name ?>
                </a>
                <? } catch (Exception $e) {} ?>
            </td>

            <td>
                <?= $job->data()['info']['title'] ?>
            </td>

            <td>
                <?= $job->data()['info']['record_date'] ?>,
                <?= @$job->data()['info']['start']['h'] ?>:<?= @$job->data()['info']['start']['m'] ?>
            </td>

            <td>
                <? $mu = sizeof($job->missing_upload_chunks()) ?>
                <? if ($mu) : ?>
                    <?= $_('An Opencast zu schickende Chunks') ?>: <?= $mu ?><br>
                <? endif ?>

                <? $ml = sizeof($job->missing_local_chunks()) ?>
                <? if ($ml) : ?>
                    <?= $_('In Stud.IP fehlende Chunks') ?>: <?= $ml ?><br>
                <? endif ?>
            </td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>
