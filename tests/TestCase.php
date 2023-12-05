<?php

namespace Comhon\CustomAction\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Comhon\CustomAction\CustomActionServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Comhon\TemplateRenderer\TemplateRendererServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Comhon\\CustomAction\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            CustomActionServiceProvider::class,
            TemplateRendererServiceProvider::class,
        ];
    }

    public function defineEnvironment($app)
    {
        $cacheDirectory = $app->useStoragePath(__DIR__.'/../storage')->storagePath('custom-action');

        tap($app['config'], function (Repository $config) use ($cacheDirectory) {
            $config->set('custom-action.user_model', \Comhon\CustomAction\Tests\Support\Models\User::class);
            $config->set('custom-action.middleware', ['api', 'can:manage-custom-action']);
            $config->set('custom-action.target_bindings', [
                'first_name',
                'name',
                'last_login_at' => 'datetime', 'verified_at' => 'date'
            ]);
            $config->set('database.default', 'testing');
            $config->set('database.connections.testing', [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ]);
            
            // Setup queue database connections.
            // $config([
            //     'queue.batching.database' => 'testing',
            //     'queue.failed.database' => 'testing',
            // ]);
        });

        $migration = include __DIR__.'/../database/migrations/create_laravel-custom-action_table.php.stub';
        $migration->up();
        $migration = include __DIR__.'/Support/Migrations/create_test_table.php';
        $migration->up();

        Gate::define('manage-custom-action', function ($user) {
            return $user->has_consumer_ability == true;
        });

        // TODO find a better way to test translations
        Lang::addLines([
            'messages.actions.send-email' => 'send email',
            'messages.actions.send-company-email' => 'send company email',
            'messages.events.company-registered' => 'company registered'
        ], 'en', 'custom-action');
    }
}
