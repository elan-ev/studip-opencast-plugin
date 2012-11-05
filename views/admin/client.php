<section>
<h1> <?=_('Capture Agent Status')?> </h1>
    <ul>
    <? foreach($agents as $agent) : ?>
        <li>
            <?= $agent['name'] ?>
            <ul>
                <li> Status: <?= $agent['state'] ?> </li>
                <li> URL: <?= $agent['url'] ?> </li>
            </ul>
        </li>
        <? endforeach ; ?>
    </ul>
</section>