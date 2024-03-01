<?php

return [
    "studip-authenticator" => function () {
        return function ($username, $password) {
            $check = StudipAuthAbstract::CheckAuthentication(
                $username,
                $password
            );

            if ($check["uid"] && $check["uid"] != "nobody") {
                return User::find($check["uid"]);
            }

            return null;
        };
    },
];
