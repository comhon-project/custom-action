<?php

use Comhon\CustomAction\Http\Controllers\ActionLocalizedSettingsController;
use Comhon\CustomAction\Http\Controllers\ActionScopedSettingsController;
use Comhon\CustomAction\Http\Controllers\ActionSettingsController;
use Comhon\CustomAction\Http\Controllers\ActionTypeController;
use Comhon\CustomAction\Http\Controllers\EventActionController;
use Comhon\CustomAction\Http\Controllers\EventController;
use Comhon\CustomAction\Http\Controllers\EventListenerController;
use Comhon\CustomAction\Http\Controllers\ManualActionController;
use Illuminate\Support\Facades\Route;

$attributes = [
    'domain' => config('custom-action.domain'),
    'prefix' => config('custom-action.route_prefix'),
    'middleware' => config('custom-action.middleware'),
];
Route::group($attributes, function () {
    Route::get('action-types/manual', [ActionTypeController::class, 'listManualActionTypes']);
    Route::get('action-types/{key}/schema', [ActionTypeController::class, 'showActionTypeSchema']);
    Route::get('manual-actions/{type}', [ManualActionController::class, 'show']);
    Route::apiResource('action-settings', ActionSettingsController::class)->only(['show', 'update']);
    Route::prefix('action-settings/{action_settings}')->group(function () {
        Route::get('scoped-settings', [ActionSettingsController::class, 'listActionScopedSettings']);
        Route::post('scoped-settings', [ActionScopedSettingsController::class, 'store']);
        Route::get('localized-settings', [ActionSettingsController::class, 'listActionLocalizedSettings']);
        Route::post('localized-settings', [ActionSettingsController::class, 'storeActionLocalizedSettings']);
    });
    Route::apiResource('scoped-settings', ActionScopedSettingsController::class)
        ->only(['show', 'update', 'destroy']);
    Route::prefix('scoped-settings/{scoped_settings}')->group(function () {
        Route::get('localized-settings', [ActionScopedSettingsController::class, 'listScopedSettingsLocalizedSettings']);
        Route::post('localized-settings', [ActionScopedSettingsController::class, 'storeScopedSettingsLocalizedSettings']);
    });
    Route::apiResource('localized-settings', ActionLocalizedSettingsController::class)
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
});
