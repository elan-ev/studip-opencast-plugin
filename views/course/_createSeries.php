<div>
    <form action="<?= PluginEngine::getLink('opencast/course/create_series/') ?>"
            method=post>
        <div style="dislay:inline;vertical-align:middle">
        </div>
        <div style="padding-top:2em;clear:both" class="form_submit">
            <?= makebutton("uebernehmen","input") ?>
            <a href="<?=PluginEngine::getLink('opencast/course/index')?>"><?= makebutton("abbrechen")?></a>
        </div>
        
    </form>
</div>

