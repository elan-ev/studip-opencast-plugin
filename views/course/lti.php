<form method="post" action="https://oc-test.virtuos.uni-osnabrueck.de/lti">
    <? foreach ($signed_data as $key => $value) : ?>
        <input type="hidden" name="<?= $key ?>" value="<?= $value ?>">
    <? endforeach ?>
    <button>Abschicken</button>
</form>
