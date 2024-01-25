<? $uid = uniqid(); ?>
<article class="studip" id="oc--widget-anchor-<?= $uid ?>">
    <? if (count($items)): ?>
        <article class="studip toggle <?= \ContentBoxHelper::classes('oc-widget-upcomings') ?>" id="oc-widget-upcomings">
            <header>
                <h1>
                    <a href="<?= \ContentBoxHelper::href('oc-widget-upcomings') ?>">
                        <?= htmlReady($texts['upcomings']) ?>
                    </a>
                </h1>
            </header>
            <? foreach ($items['upcomings'] as $item): ?>
                <article class="studip">
                    <section style="display:flex;justify-content: space-between;">
                        <a href="<?= $item['url'] ?>" style="white-space:nowrap;overflow: hidden;text-overflow:ellipsis;">
                            <?= Icon::create('seminar', 'clickable')->asImg(['class' => 'text-bottom']) ?>
                            <?= htmlReady($item['course']->getFullname('name'))?>
                        </a>
                        <span style="flex-shrink:0;">
                            <?= $item['date']->getFullname() ?>
                        </span>
                    </section>
                </article>
            <? endforeach; ?>
        </article>
    <? else: ?>
        <section>
            <?= htmlReady($texts['empty']) ?>
        </section>
    <? endif; ?>
</article>
