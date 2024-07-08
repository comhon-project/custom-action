<?php

use Comhon\CustomAction\Http\Controllers\ActionLocalizedSettingsController;
use Comhon\CustomAction\Http\Controllers\ActionScopedSettingsController;
use Comhon\CustomAction\Http\Controllers\ActionSettingsController;
use Comhon\CustomAction\Http\Controllers\CustomActionController;
use Comhon\CustomAction\Http\Controllers\CustomEventController;
use Comhon\CustomAction\Http\Controllers\CustomEventListenerController;
use Illuminate\Support\Facades\Route;

$attributes = [
    'domain' => config('custom-action.domain'),
    'prefix' => config('custom-action.route_prefix'),
    'middleware' => config('custom-action.middleware'),
];
Route::group($attributes, function () {
    Route::get('unique-actions', [CustomActionController::class, 'listUniqueActions']);
    Route::get('actions/{key}/schema', [CustomActionController::class, 'showActionSchema']);
    Route::apiResource('action-settings', ActionSettingsController::class)->only(['show', 'update']);
    Route::prefix('action-settings/{custom_action_settings}')->group(function () {
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

    Route::get('events', [CustomEventController::class, 'listEvents']);
    Route::prefix('events/{event}')->group(function () {
        Route::get('schema', [CustomEventController::class, 'showEventSchema']);
        Route::get('listeners', [CustomEventController::class, 'listEventListeners']);
        Route::post('listeners', [CustomEventListenerController::class, 'store']);
    });
    Route::apiResource('event-listeners', CustomEventListenerController::class)->only(['update', 'destroy']);
    Route::prefix('event-listeners/{event_listener}')->group(function () {
        Route::get('actions', [CustomEventListenerController::class, 'listEventListenerActions']);
        Route::post('actions', [CustomEventListenerController::class, 'storeEventListenerAction']);
        Route::post('actions/sync', [CustomEventListenerController::class, 'syncEventListenerAction']);
        Route::post('actions/{custom_action_settings}/remove', [CustomEventListenerController::class, 'removeEventListenerAction']);
    });
});
