<!DOCTYPE html>
<html>
    <head>
        <style>
            html, body, * {
                margin: 0px;
                padding: 0px;
            }
            img {
                width: 100%;
                z-index: 5;
            }
            span {
                display: block;
                position: absolute;
                top: 35vh;
                text-align: center;
                font-size: 4em;
                z-index: 10;
                width: 100%;
                font-weight: bold;
                text-shadow: -2px 0 white, 0 2px white, 2px 0 white, 0 -2px white;
            }
        </style>
    </head>
    <body>
        <img src="<?= $plugin->getPluginURL() . '/images/default-preview.png' ?>">
        <span><?= $_('Es wurde bisher kein Video ausgewählt!') ?></span>
    </body>
</html>