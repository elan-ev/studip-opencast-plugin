<?php

namespace Opencast\GraphQL;

use Opencast\GraphQL\Resolvers\Events;

return [
    'Query' => [
        'getEvents' => function($root, $args, $context) {
            return Events::getEvents($root, $args, $context);
        },
        'getCountEvents' => function($root, $args, $context) {
            return Events::getCountEvents($root, $args, $context);
        }
    ],
    'Mutation' => [
        'removeEvent' => function($root, $args, $context) {
            return Events::removeEvent($root, $args, $context);
        },
        'addEvent' => function($root, $args, $context) {
            return Events::addEvent($root, $args, $context);
        }
    ]
];
