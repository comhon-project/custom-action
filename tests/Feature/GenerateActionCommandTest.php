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
    #[DataProvider('providerGenerateActionFileSuccess')]
    public function test_generate_action_file_success($dirShouldExists, $extends, $manual, $event, $hasBindings, $expectContent)
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
        artisan($this, 'custom-action:generate', [
            'name' => 'TestGenericSendEmail',
            ...($extends ?
                ['--extends' => $extends, '--manual' => $manual, '--event' => $event, '--has-bindings' => $hasBindings]
                : ['--manual' => $manual, '--event' => $event, '--has-bindings' => $hasBindings]),
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
                false,
                false,
                false,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\Action;
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
        InteractWithSettingsTrait;

    public function __construct(protected Action \$action) {}

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
                'send-email',
                false,
                false,
                false,
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
                false,
                false,
                false,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use App\Actions\BadAction;
use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\Action;
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
        InteractWithSettingsTrait;

    public function __construct(protected Action \$action) {}

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
                'send-email',
                true,
                true,
                false,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\HandleManualActionTrait;
use Comhon\CustomAction\Actions\SendEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestGenericSendEmail extends SendEmail
{
    use HandleManualActionTrait;

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
                null,
                true,
                true,
                true,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\HandleManualActionTrait;
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
        HandleManualActionTrait,
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
                'my-callable-from-event',
                true,
                false,
                false,
                <<<EOT
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use App\Actions\MyCallableFromEvent;
use Comhon\CustomAction\Actions\HandleManualActionTrait;
use Comhon\CustomAction\Bindings\EventBindingsContainer;
use Comhon\CustomAction\Models\Action;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestGenericSendEmail extends MyCallableFromEvent
{
    use HandleManualActionTrait;

    public function __construct(
        protected Action \$action,
        protected ?EventBindingsContainer \$eventBindingsContainer = null,
    ) {}

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
        ];
    }

    public function test_generate_action_file_failure()
    {
        app()->useAppPath(Utils::getAppPath());

        $this->expectExceptionMessage("invalid extends parameter 'failure'");
        artisan($this, 'custom-action:generate', [
            'name' => 'TestGenericSendEmail',
            '--extends' => 'failure',
        ]);
    }
}
