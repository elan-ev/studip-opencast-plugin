<label>
    <span <?= $config['required'] ? 'class="required"' : '' ?>>
        <?= htmlReady($_($config['description'])) ?>
    </span>


    <div class="input-group files-search oc_password_vis">

        <input type="password"
            value="<?= $config['value'] ? '*****' : '' ?>"
            data-type="password" onChange="OC.updateHiddenPassword(this)"
        >

        <input type="hidden"
            value="<?= $config['value'] ?>"
            name="config[<?= $config_id ?>][<?= $config['name'] ?>]"
        >

        <span class="input-group-append ">
            <button type="submit" class="button" onClick="return OC.togglePasswordVis(this)">
                <?= Icon::create('visibility-visible', Icon::ROLE_CLICKABLE, [
                    'data-name' => 'visible'
                ]) ?>
                <?= Icon::create('visibility-invisible', Icon::ROLE_CLICKABLE, [
                    'data-name' => 'invisible'
                ]) ?>
            </button>
        </span>
    </div>
</label>
