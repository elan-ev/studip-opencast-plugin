<?php

return [
    "studip-current-user" => function () {
        $user = $GLOBALS["user"];
        if ($user) {
            return $user->getAuthenticatedUser();
        }

        return null;
    },

    "api-token" => function () {
        return \Config::get()->OPENCAST_API_TOKEN;
    },
];
