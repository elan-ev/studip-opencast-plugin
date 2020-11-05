<div>
    <span <?= $config['required'] ? 'class="required"' : '' ?>>
        <?= $config['description'] ?>
    </span>
</div>

<section class="hgroup size-s">
    <label>
        <input type="radio" value="1"
            name="config[<?= $config_id ?>][<?= $config['name'] ?>]"
            <?= $config['value'] ? 'checked="checked"' : '' ?>
        >
        <?= $_('Ja') ?>
    </label>

    <label>
        <input type="radio" value="0"
            name="config[<?= $config_id ?>][<?= $config['name'] ?>]"
            <?= !$config['value'] ? 'checked="checked"' : '' ?>
        >
        <?= $_('Nein') ?>
    </label>
</section>
