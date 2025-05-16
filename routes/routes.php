<?php

use Comhon\CustomAction\Http\Controllers\ActionSchemaController;
use Comhon\CustomAction\Http\Controllers\DefaultSettingController;
use Comhon\CustomAction\Http\Controllers\EventActionController;
use Comhon\CustomAction\Http\Controllers\EventController;
use Comhon\CustomAction\Http\Controllers\EventListenerController;
use Comhon\CustomAction\Http\Controllers\LocalizedSettingController;
use Comhon\CustomAction\Http\Controllers\ManualActionController;
use Comhon\CustomAction\Http\Controllers\ScopedSettingController;
use Illuminate\Support\Facades\Route;

$attributes = [
    'domain' => config('custom-action.domain'),
    'prefix' => config('custom-action.route_prefix'),
    'middleware' => config('custom-action.middleware'),
];
Route::group($attributes, function () {
    Route::get('actions/{type}/schema', [ActionSchemaController::class, 'showActionSchema']);

    Route::get('manual-actions', [ManualActionController::class, 'listTypes']);
    Route::prefix('manual-actions/{type}')->group(function () {
        Route::get('', [ManualActionController::class, 'show']);
        Route::get('scoped-settings', [ManualActionController::class, 'listScopedSettings']);
        Route::post('default-settings', [ManualActionController::class, 'storeDefaultSetting']);
        Route::post('scoped-settings', [ManualActionController::class, 'storeScopedSetting']);
        Route::post('simulate', [ManualActionController::class, 'simulate']);
    });

    Route::apiResource('default-settings', DefaultSettingController::class)->only(['show', 'update']);
    Route::prefix('default-settings/{default_setting}')->group(function () {
        Route::get('localized-settings', [DefaultSettingController::class, 'listDefaultLocalizedSettings']);
        Route::post('localized-settings', [DefaultSettingController::class, 'storeDefaultLocalizedSetting']);
    });

    Route::apiResource('scoped-settings', ScopedSettingController::class)
        ->only(['show', 'update', 'destroy']);
    Route::prefix('scoped-settings/{scoped_setting}')->group(function () {
        Route::get('localized-settings', [ScopedSettingController::class, 'listScopedLocalizedSettings']);
        Route::post('localized-settings', [ScopedSettingController::class, 'storeScopedLocalizedSetting']);
    });

    Route::apiResource('localized-settings', LocalizedSettingController::class)
        ->only(['show', 'update', 'destroy']);

    Route::get('events', [EventController::class, 'listEvents']);
    Route::prefix('events/{event}')->group(function () {
        Route::get('schema', [EventController::class, 'showEventSchema']);
        Route::get('listeners', [EventController::class, 'listEventListeners']);
        Route::post('listeners', [EventListenerController::class, 'store']);
    });

    Route::apiResource('event-listeners', EventListenerController::class)->only(['update', 'destroy']);
    Route::prefix('event-listeners/{event_listener}')->group(function () {
        Route::get('actions', [EventListenerController::class, 'listEventListenerActions']);
        Route::post('actions', [EventActionController::class, 'store']);
    });

    Route::apiResource('event-actions', EventActionController::class)->only(['show', 'update', 'destroy']);
    Route::prefix('event-actions/{event_action}')->group(function () {
        Route::get('scoped-settings', [EventActionController::class, 'listScopedSettings']);
        Route::post('default-settings', [EventActionController::class, 'storeDefaultSetting']);
        Route::post('scoped-settings', [EventActionController::class, 'storeScopedSetting']);
        Route::post('simulate', [EventActionController::class, 'simulate']);
    });
});
