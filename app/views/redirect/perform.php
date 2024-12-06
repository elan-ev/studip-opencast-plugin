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
        <style>
            html, body, form {
                margin: 0;
            }
            .oc--loading-spinner {
                height: 100vh;
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .oc--spinner {
                transform: scale(0.5);
            }
            .oc--spinner:after {
                content: " ";
                display: block;
                width: 64px;
                height: 64px;
                border-radius: 50%;
                border: 6px solid;
                border-color: #28497c transparent #28497c transparent;
                animation: oc-spinner 1.2s linear infinite;
            }
            @keyframes oc-spinner {
                0% {
                    transform: rotate(0deg);
                }
                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
    </head>
    <body>
        <div class="oc--loading-spinner">
            <div class="oc--spinner"></div>
        </div>
        <form class="default" action="<?= $launch_url ?>" name="ltiLaunchForm" id="ltiLaunchForm" method="post" encType="application/x-www-form-urlencoded">
            <? foreach ($launch_data as $name => $value) : ?>
                <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
            <? endforeach ?>
        </form>
    </body>
<? endif ?>
</html>
