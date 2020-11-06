<label>
    <span <?= $config['required'] ? 'class="required"' : '' ?>>
        <?= $config['description'] ?>
    </span>

    <input type="text"
        value="<?= $config['value'] ?>"
        name="config[<?= $config_id ?>][<?= $config['name'] ?>]"
    >
</label>
