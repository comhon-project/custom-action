<?php

return [

    /*
     | prefix to apply on all custom action routes
     */
    'prefix' => 'custom',

    /*
     | middlewares to apply on all custom action routes
     */
    'middleware' => ['web', 'auth', 'can:manage-custom-action'],

    /*
     | your app user model
     */
    'user_model' => App\Models\User::class,

    /*
     | the model resolver.
     | it must be instance of Comhon\ModelResolverContract\ModelResolverInterface.
     | it must be registered as singleton
     */
    'model_resolver' => Comhon\CustomAction\Resolver\ModelResolver::class,

    /*
     | list of bindings that may be applied when action target a user
     | * might be a simple array of properties :
     |     ['first_name', 'last_name', 'address'],
     | * might be a keyed array, each key is a property name, each value is the property type :
     |     ['first_name' => 'string', 'birth_date' => 'date'],
     | * might be a function that return one of previous format.
     */
    'target_bindings' => [],
];
