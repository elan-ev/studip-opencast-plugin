<form class="default" action="<?= $controller->url_for('course/accept_tos') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend><?= $_('Nutzungsvereinbarung') ?></legend>
        <div>
            <?= formatReady($tos_text) ?>
        </div>
    </fieldset>
    <footer>
        <?= Studip\Button::createAccept($_('Nutzungsvereinbarung akzeptieren')) ?>
    </footer>
</form>
