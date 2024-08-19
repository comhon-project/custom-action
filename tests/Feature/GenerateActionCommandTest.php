<?php

namespace Tests\Feature;

use App\Actions\BadAction;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Tests\Support\Utils;
use Tests\TestCase;

use function Orchestra\Testbench\artisan;

class GenerateActionCommandTest extends TestCase
{
    /**
     * @dataProvider providerGenerateActionFileSuccess
     */
    public function testGenerateActionFileSuccess($dirShouldExists, $extends, $expectContent)
    {
        CustomActionModelResolver::bind('bad-action', BadAction::class);
        $dir = Utils::joinPaths(Utils::getTestPath('Actions'), 'CustomActions');
        if (file_exists($dir)) {
            rmdir($dir);
        }
        if ($dirShouldExists) {
            mkdir($dir, 0775, true);
        }
        app()->useAppPath(Utils::getTestPath());
        artisan($this, 'custom-action:generate', [
            'name' => 'TestGenericSendEmail',
            ...($extends ? ['--extends' => $extends] : []),
        ]);

        $path = Utils::joinPaths(Utils::getTestPath('Actions'), 'CustomActions', 'TestGenericSendEmail.php');
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
                <<<EOT
<?php

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\ActionSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestGenericSendEmail implements CustomActionInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ActionSettings \$actionSettings,
        private ?BindingsContainerInterface \$bindingsContainer = null,
    ) {
        //
    }

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
                <<<EOT
<?php

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestGenericSendEmail extends SendTemplatedMail
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
                <<<EOT
<?php

namespace App\Actions\CustomActions;

use App\Actions\BadAction;
use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\ActionSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestGenericSendEmail extends BadAction implements CustomActionInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ActionSettings \$actionSettings,
        private ?BindingsContainerInterface \$bindingsContainer = null,
    ) {
        //
    }

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

    public function testGenerateActionFileFailure()
    {
        app()->useAppPath(Utils::getTestPath());

        $this->expectExceptionMessage("invalid extends parameter 'failure'");
        artisan($this, 'custom-action:generate', [
            'name' => 'TestGenericSendEmail',
            '--extends' => 'failure',
        ]);
    }
}
