<?php

namespace App\Providers;

use Comhon\CustomAction\Catalogs\EventCatalog;
use Comhon\CustomAction\Catalogs\ManualActionTypeCatalog;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ManualActionTypeCatalog::class, function ($app) {
            return new ManualActionTypeCatalog([
                'send-company-email',
            ]);
        });
        $this->app->singleton(EventCatalog::class, function ($app) {
            return new EventCatalog(function () {
                return [
                    'company-registered',
                ];
            });
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::view('/', 'welcome');
    }
}
