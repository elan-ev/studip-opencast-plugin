<label>
    <span <?= $config['required'] ? 'class="required"' : '' ?>>
        <?= htmlReady($_($config['description'])) ?>
    </span>

    <input type="number"
        value="<?= $config['value'] ?>"
        name="config[<?= $config_id ?>][<?= $config['name'] ?>]"
    >
</label>
