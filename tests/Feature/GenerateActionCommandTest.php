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
    public function test_generate_action_file_success(
        $dirShouldExists,
        $extends,
        $callable,
        $hasContext,
        $hasTranslatableContext,
        $expectContent
    ) {
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
                [
                    '--extends' => $extends,
                    '--callable' => $callable,
                    '--has-context' => $hasContext,
                    '--has-translatable-context' => $hasTranslatableContext,
                ]
                : [
                    '--callable' => $callable,
                    '--has-context' => $hasContext,
                    '--has-translatable-context' => $hasTranslatableContext,
                ]),
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
                false,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\CallableManually;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
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
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        CallableManually;

    public static function getSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }

    public static function getLocalizedSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }

    public function handle()
    {
        return;
    }
}

EOT
            ],
            [
                false,
                null,
                'from-event',
                true,
                false,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasContextInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestGenericSendEmail implements CustomActionInterface, CallableFromEventInterface, HasContextInterface
{
    use Dispatchable,
        Queueable,
        InteractsWithQueue,
        SerializesModels,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        CallableFromEventTrait;

    public static function getSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }

    public static function getLocalizedSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }

    public static function getContextSchema(): array
    {
        return [];
    }

    public function getContext(): array
    {
        return [];
    }

    public function handle()
    {
        return;
    }
}

EOT
            ],
            [
                false,
                'SendAutomaticEmail',
                'from-event',
                false,
                true,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\SendAutomaticEmail;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestGenericSendEmail extends SendAutomaticEmail implements HasTranslatableContextInterface
{
    public static function getSettingsSchema(?string \$eventClassContext = null): array
    {
        return parent::getSettingsSchema(\$eventClassContext);
    }

    public static function getLocalizedSettingsSchema(?string \$eventClassContext = null): array
    {
        return parent::getLocalizedSettingsSchema(\$eventClassContext);
    }

    public static function getContextSchema(): array
    {
        return parent::getContextSchema();
    }

    public function getContext(): array
    {
        return parent::getContext();
    }

    public static function getTranslatableContext(): array
    {
        return [];
    }
}

EOT
            ],
            [
                false,
                'bad-action',
                'manually',
                false,
                false,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use App\Actions\BadAction;
use Comhon\CustomAction\Actions\CallableManually;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
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
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        CallableManually;

    public static function getSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }

    public static function getLocalizedSettingsSchema(?string \$eventClassContext = null): array
    {
        return [];
    }

    public function handle()
    {
        return;
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
