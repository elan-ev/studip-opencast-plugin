<? if (!empty($this->error)) : ?>
<DOCTYPE html>
<html>
    <head>
        <style>
            h1 {
                display: block;
                position: absolute;
                width: 70%;
                left: 50%;
                margin-left: -35%;
                top: 20%;
                text-align: center;
            }

            .oc-logo {
                width: 20%;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <h1>
            <img class="oc-logo" src="<?= $assets_url ?>/images/opencast.svg">
            <br>
            <?= htmlReady($this->error) ?>
        </h1>
    </body>
</html>
<? else : ?>
<form class="default" action="<?= $launch_url ?>" name="ltiLaunchForm" id="ltiLaunchForm" method="post" encType="application/x-www-form-urlencoded">
    <div class="oc--loading-spinner oc--loading-redirect">
        <div class="oc--spinner"></div>
    </div>
    <? foreach ($launch_data as $name => $value) : ?>
        <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
    <? endforeach ?>
</form>

<script type="text/javascript">
    jQuery(function ($) {
        $('#ltiLaunchForm').submit();
    });
</script>
<? endif ?>