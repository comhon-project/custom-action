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
     | your app user model
     */
    'user_model' => App\Models\User::class,

    /*
     | custom action library come up with some built in validation rules.
     | theses function are registered in the laravel validator as string,
     | so they may conflict with your rules if you have also set any.
     | in this case you can define a prefix for all custom action validation
     | rules to avoid conflicts.
     */
    'rule_prefix' => '',

    /*
     | the model resolver.
     | it must be instance of Comhon\ModelResolverContract\ModelResolverInterface.
     | it must be registered as singleton
     */
    'model_resolver' => Comhon\CustomAction\Resolver\ModelResolver::class,

    /*
     | actions that may be defined as unique actions.
     | each element must a class that implements CustomActionInterface.
     */
    'unique_actions' => [],

    /*
     | events that may be linked to actions
     | each element must be a class that implements CustomEventInterface.
     */
    'events' => [],
];
