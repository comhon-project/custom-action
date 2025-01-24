<?php

namespace Tests;

use App\Providers\WorkbenchServiceProvider;
use Comhon\CustomAction\CustomActionServiceProvider;
use Comhon\TemplateRenderer\TemplateRendererServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
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
            WorkbenchServiceProvider::class,
            CustomActionServiceProvider::class,
            TemplateRendererServiceProvider::class,
        ];
    }

    public function defineEnvironment($app)
    {
        // Warning! set a specific config for only one test
        $shoudQueueDispatcher = $this->name() == 'testShouldQueueDispatcher';

        $cacheDirectory = $app->useStoragePath(Utils::getBasePath('storage'))->storagePath('custom-action');

        tap($app['config'], function (Repository $config) use ($shoudQueueDispatcher) {
            $config->set('custom-action.event_action_dispatcher.should_queue', $shoudQueueDispatcher);
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
