<?php

namespace Opencast\GraphQL;

use Opencast\GraphQL\Resolvers\Events;

return [
    'Query' => [
        'getEvents' => function($root, $args, $context) {
            return Events::getEvents($root, $args, $context);
        }
    ]
];
