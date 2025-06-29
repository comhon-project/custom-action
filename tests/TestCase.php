<?php

namespace Tests;

use App\Providers\WorkbenchServiceProvider;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\CustomActionServiceProvider;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\TemplateRenderer\TemplateRendererServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Assert;
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
        $shoudQueueDispatcher = $this->name() == 'test_should_queue_dispatcher';

        $cacheDirectory = $app->useStoragePath(Utils::getBasePath('storage'))->storagePath('custom-action');

        tap($app['config'], function (Repository $config) use ($shoudQueueDispatcher) {
            $config->set('custom-action.event_action_dispatcher.should_queue', $shoudQueueDispatcher);
            $config->set('custom-action.use_policies', true);
            $config->set('custom-action.middleware', ['api']);

            // Setup queue database connections.
            // $config([
            //     'queue.batching.database' => 'testing',
            //     'queue.failed.database' => 'testing',
            // ]);
        });

        $this->setPoliciesFiles();

        if (! Schema::hasTable('custom_action_settings')) {
            $migration = include Utils::joinPaths(Utils::getBasePath(), 'database', 'migrations', 'create_laravel-custom-action_table.php.stub');
            $migration->up();
            $migration = include Utils::joinPaths(Utils::getBasePath(), 'workbench', 'database', 'migrations', 'create_test_table.php');
            $migration->up();
        }
    }

    public function setPoliciesFiles()
    {
        $stubPolicyDir = Utils::getBasePath('policies');
        $testPolicyDir = Utils::joinPaths(Utils::getAppPath(), 'Policies', 'CustomAction');

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

    /**
     * Asserts that an array has a specified subset.
     *
     * @param  \ArrayAccess|array  $subset
     * @param  \ArrayAccess|array  $array
     */
    public static function assertArraySubset($subset, $array, bool $checkForIdentity = false, string $msg = ''): void
    {
        Assert::assertArraySubset($subset, $array, $checkForIdentity, $msg);
    }

    public static function providerBoolean()
    {
        return [
            [true],
            [false],
        ];
    }

    public function getUniqueName($actionClass)
    {
        return CustomActionModelResolver::getUniqueName($actionClass)
            ?? throw new \Exception("action $actionClass not registered");
    }

    protected function getActionDefaultSetting(
        string $actionClass,
        ?array $defaultSettings = null,
        ?array $localizedSettingsByLocales = null
    ): DefaultSetting {
        $factory = is_subclass_of($actionClass, CallableFromEventInterface::class)
            ? EventAction::factory()
            : ManualAction::factory();

        return $factory->action($actionClass)
            ->withDefaultSettings($defaultSettings, $localizedSettingsByLocales)
            ->create()
            ->defaultSetting;
    }

    protected function getActionScopedSetting(
        string $actionClass,
        ?array $scope = null,
        ?array $defaultSettings = null,
        ?array $localizedSettingsByLocales = null
    ): ScopedSetting {
        $factory = is_subclass_of($actionClass, CallableFromEventInterface::class)
            ? EventAction::factory()
            : ManualAction::factory();

        return $factory->action($actionClass)
            ->withScopedSettings($scope, $defaultSettings, $localizedSettingsByLocales)
            ->create()
            ->scopedSettings
            ->first();
    }
}
