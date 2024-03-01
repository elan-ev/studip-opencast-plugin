<?php

return [
    "token" => function () {
        return bin2hex(random_bytes(8));
    },
];
