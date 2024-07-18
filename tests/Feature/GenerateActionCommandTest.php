<?php

namespace Tests\Feature;

use App\Models\User;
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
        CustomActionModelResolver::bind('user', User::class);
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

use Comhon\CustomAction\Contracts\CustomActionInterface;

class TestGenericSendEmail implements CustomActionInterface
{

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

class TestGenericSendEmail extends SendTemplatedMail
{

}

EOT
            ],
            [
                false,
                'user',
                <<<EOT
<?php

namespace App\Actions\CustomActions;

use App\Models\User;
use Comhon\CustomAction\Contracts\CustomActionInterface;

class TestGenericSendEmail extends User implements CustomActionInterface
{

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
