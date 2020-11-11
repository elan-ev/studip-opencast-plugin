<form class="default" action="<?= $controller->url_for('course/accept_tos') ?>" method="post">
    <fieldset>
        <legend><?= $_('Datenschutzrichtlinien') ?></legend>
        <p>
            <?= formatReady(Config::get()->OPENCAST_TOS) ?>
        </p>
    </fieldset>
    <footer>
        <?= Studip\Button::createAccept($_('Datenschutzrichtlinien akzeptieren')) ?>
    </footer>
</form>
