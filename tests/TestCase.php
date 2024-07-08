<?php

namespace Tests;

use Comhon\CustomAction\CustomActionServiceProvider;
use Comhon\TemplateRenderer\TemplateRendererServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Lang;
use Orchestra\Testbench\TestCase as Orchestra;
use Tests\Support\Utils;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory'
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
        $cacheDirectory = $app->useStoragePath(Utils::getBasePath('storage'))->storagePath('custom-action');

        tap($app['config'], function (Repository $config) {
            $config->set('custom-action.use_policies', true);
            $config->set('custom-action.middleware', ['api']);
            $config->set('database.default', 'testing');
            $config->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'route_prefix' => '',
            ]);

            // Setup queue database connections.
            // $config([
            //     'queue.batching.database' => 'testing',
            //     'queue.failed.database' => 'testing',
            // ]);
        });

        $this->setPoliciesFiles();

        $migration = include Utils::joinPaths(Utils::getBasePath(), 'database', 'migrations', 'create_laravel-custom-action_table.php.stub');
        $migration->up();
        $migration = include Utils::joinPaths(Utils::getTestPath(), 'Migrations', 'create_test_table.php');
        $migration->up();

        // TODO find a better way to test translations
        Lang::addLines([
            'messages.actions.send-email' => 'send email',
            'messages.actions.queue-email' => 'send email',
            'messages.actions.send-company-email' => 'send company email',
            'messages.events.company-registered' => 'company registered',
        ], 'en', 'custom-action');
    }

    public function setPoliciesFiles()
    {
        $stubPolicyDir = Utils::getBasePath('policies');
        $testPolicyDir = Utils::joinPaths(Utils::getTestPath('App'), 'Policies', 'CustomAction');

        if (file_exists($testPolicyDir)) {
            $files = array_diff(scandir($testPolicyDir), ['.', '..']);
            foreach ($files as $file) {
                unlink(Utils::joinPaths($testPolicyDir, $file));
            }
            rmdir($testPolicyDir);
        }
        mkdir($testPolicyDir, 0775, true);

        $files = array_diff(scandir($stubPolicyDir), ['.', '..']);
        foreach ($files as $file) {
            $policy = str_replace(
                '// TODO put your authorization logic here',
                'return $user->has_consumer_ability == true;',
                file_get_contents(Utils::joinPaths($stubPolicyDir, $file)),
            );
            file_put_contents(Utils::joinPaths($testPolicyDir, $file), $policy);
        }
    }
}
