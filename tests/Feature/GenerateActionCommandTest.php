<?php

namespace Tests\Feature;

use App\Actions\BadAction;
use App\Actions\MyCallableFromEvent;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Utils;
use Tests\TestCase;

use function Orchestra\Testbench\artisan;

class GenerateActionCommandTest extends TestCase
{
    public function test_console_command_sucess(): void
    {
        $this->artisan('custom-action:generate MyAction')
            ->expectsQuestion('How will you invoke your action?', 'manually')
            ->assertExitCode(0);
    }

    public function test_console_command_invalid_callable(): void
    {
        $this->artisan('custom-action:generate MyAction --callable=foo')
            ->expectsOutput("invalid input callable 'foo'")
            ->expectsQuestion('How will you invoke your action?', 'manually')
            ->assertExitCode(0);
    }

    public function test_console_command_failure(): void
    {
        $this->expectExceptionMessage("invalid input callable 'foo'");
        $this->artisan('custom-action:generate MyAction')
            ->expectsQuestion('How will you invoke your action?', 'foo')
            ->assertExitCode(0);
    }

    #[DataProvider('providerGenerateActionFileSuccess')]
    public function test_generate_action_file_success($dirShouldExists, $extends, $callable, $hasBindings, $expectContent)
    {
        CustomActionModelResolver::bind('bad-action', BadAction::class);
        CustomActionModelResolver::bind('my-callable-from-event', MyCallableFromEvent::class);

        $dir = Utils::joinPaths(Utils::getAppPath('Actions'), 'CustomActions');
        if (file_exists($dir)) {
            rmdir($dir);
        }
        if ($dirShouldExists) {
            mkdir($dir, 0775, true);
        }
        app()->useAppPath(Utils::getAppPath());
        $this->artisan('custom-action:generate', [
            'name' => 'TestGenericSendEmail',
            ...($extends ?
                ['--extends' => $extends, '--callable' => $callable, '--has-bindings' => $hasBindings]
                : ['--callable' => $callable, '--has-bindings' => $hasBindings]),
        ]);

        $path = Utils::joinPaths(Utils::getAppPath('Actions'), 'CustomActions', 'TestGenericSendEmail.php');
        $fileContent = file_exists($path) ? file_get_contents($path) : null;
        if ($fileContent !== null) {
            unlink($path);
        }
        $this->assertNotNull($fileContent, "file doesn't exist");
        $this->assertEquals($expectContent, $fileContent);
    }

    public static function providerGenerateActionFileSuccess()
    {
        return [
            [
                true,
                null,
                'manually',
                false,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\CallableManually;
use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestGenericSendEmail implements CustomActionInterface
{
    use Dispatchable,
        Queueable,
        InteractsWithQueue,
        SerializesModels,
        InteractWithBindingsTrait,
        InteractWithSettingsTrait,
        CallableManually;

    /**
     * Get action settings schema
     */
    public static function getSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }


    /**
     * execute action
     */
    public function handle(): void
    {
        //
    }
}

EOT
            ],
            [
                false,
                null,
                'from-event',
                true,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestGenericSendEmail implements CustomActionInterface, CallableFromEventInterface, HasBindingsInterface
{
    use Dispatchable,
        Queueable,
        InteractsWithQueue,
        SerializesModels,
        InteractWithBindingsTrait,
        InteractWithSettingsTrait,
        CallableFromEventTrait;

    /**
     * Get action settings schema
     */
    public static function getSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }


    /**
     * execute action
     */
    public function handle(): void
    {
        //
    }
}

EOT
            ],
            [
                false,
                'SendEmail',
                'from-event',
                true,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\SendEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestGenericSendEmail extends SendEmail
{
    /**
     * Get action settings schema
     */
    public static function getSettingsSchema(?string \$eventClassContext = null): array
    {
        return parent::getSettingsSchema(\$eventClassContext);
    }

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string \$eventClassContext = null): array
    {
        return parent::getLocalizedSettingsSchema(\$eventClassContext);
    }


    /**
     * execute action
     */
    public function handle(): void
    {
        //
    }
}

EOT
            ],
            [
                false,
                'bad-action',
                'manually',
                false,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use App\Actions\BadAction;
use Comhon\CustomAction\Actions\CallableManually;
use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestGenericSendEmail extends BadAction implements CustomActionInterface
{
    use Dispatchable,
        Queueable,
        InteractsWithQueue,
        SerializesModels,
        InteractWithBindingsTrait,
        InteractWithSettingsTrait,
        CallableManually;

    /**
     * Get action settings schema
     */
    public static function getSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }


    /**
     * execute action
     */
    public function handle(): void
    {
        //
    }
}

EOT
            ],
        ];
    }

    public function test_generate_action_file_failure()
    {
        app()->useAppPath(Utils::getAppPath());

        $this->expectExceptionMessage("invalid extends parameter 'failure'");
        artisan($this, 'custom-action:generate', [
            'name' => 'TestGenericSendEmail',
            '--callable' => 'manually',
            '--extends' => 'failure',
        ]);
    }
}
