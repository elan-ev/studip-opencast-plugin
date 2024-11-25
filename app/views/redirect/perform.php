<DOCTYPE html>
<html>
<? if (!empty($this->error)) : ?>
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
<? else : ?>
    <head>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById('ltiLaunchForm').submit();
            });
        </script>
    </head>
    <body>
        <form class="default" action="<?= $launch_url ?>" name="ltiLaunchForm" id="ltiLaunchForm" method="post" encType="application/x-www-form-urlencoded">
            <? foreach ($launch_data as $name => $value) : ?>
                <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
            <? endforeach ?>
        </form>
    </body>
<? endif ?>
</html>
