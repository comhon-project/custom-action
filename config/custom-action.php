<?php

return [

    /*
     | prefix to apply on all custom action routes
     */
    'route_prefix' => 'custom',

    /*
     | middlewares to apply on all custom action routes
     */
    'middleware' => ['web', 'auth'],

    /*
     | if you want to define user access using policies, set this config to true.
     | don't forget to publish policies in your project in order to use policies.
     */
    'use_policies' => false,

    /*
     | custom action library come up with some built in validation rules.
     | theses function are registered in the laravel validator as string,
     | so they may conflict with your rules if you have also set any.
     | in this case you can define a prefix for all custom action validation
     | rules to avoid conflicts.
     */
    'rule_prefix' => '',

    /*
     | event action dispatcher config
     */
    'event_action_dispatcher' => [

        /*
        | determine if the event action dispatcher is queued
        */
        'should_queue' => false,

        /*
        | if the event action dispatcher is queued, determine the queue connection.
        | if not set, the default connection is used.
        */
        'queue_connection' => null,

        /*
        | if the event action dispatcher is queued, determine the queue to use.
        | if not set, the default queue of the defined connection is used.
        */
        'queue_name' => null,
    ],

    /*
     | actions that may be defined as manual actions.
     | each element must a class that implements CustomActionInterface.
     */
    'manual_actions' => [],

    /*
     | events that may be linked to actions
     | each element must be a class that implements CustomEventInterface.
     */
    'events' => [],
];
