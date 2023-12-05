<?php

namespace Tests\Feature;
use function Orchestra\Testbench\artisan;
use Comhon\CustomAction\Tests\TestCase;

class GenerateActionCommandTest extends TestCase
{
    /**
     * @dataProvider providerGenerateActionFileSuccess
     */
    public function testGenerateActionFileSuccess($generic, $dirShouldExists, $expectContent)
    {
        $sep = DIRECTORY_SEPARATOR;
        $dir = __DIR__ . "{$sep}..{$sep}Console{$sep}Commands";
        if (file_exists($dir)) {
            rmdir($dir);
        }
        if ($dirShouldExists) {
            mkdir($dir, 0775, true);
        }
        app()->useAppPath(__DIR__ . $sep . '..');
        artisan($this, 'custom-action:generate', [
            'name' => 'TestGenericSendEmail',
            '--extends' => 'send-email',
            '--generic' => $generic,
        ]);

        $path = __DIR__ . "{$sep}..{$sep}Console{$sep}Commands{$sep}TestGenericSendEmail.php";
        $this->assertFileExists($path);
        $this->assertEquals($expectContent, file_get_contents($path));
        unlink($path);
    }

    public static function providerGenerateActionFileSuccess()
    {
        return [
            [
                true,
                true,
                <<<EOT
<?php

namespace App\Console\Commands;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Contracts\CustomActionInterface;

class TestGenericSendEmail extends SendTemplatedMail implements CustomActionInterface
{
    public function getBindingSchema(): array
    {
        return [
            ...parent::getBindingSchema(),
            // Here goes your specific action bindings
        ];
    }
}

EOT
            ],
            [
                false,
                false,
                <<<EOT
<?php

namespace App\Console\Commands;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;

class TestGenericSendEmail extends SendTemplatedMail implements CustomUniqueActionInterface
{
    public function getBindingSchema(): array
    {
        return [
            ...parent::getBindingSchema(),
            // Here goes your specific action bindings
        ];
    }
}

EOT
            ],
        ];
    }

    public function testGenerateActionFileFailure()
    {
        app()->useAppPath(__DIR__.'/..');
        
        $this->expectExceptionMessage("invalid extends parameter 'failure'");
        artisan($this, 'custom-action:generate', [
            'name' => 'TestGenericSendEmail',
            '--extends' => 'failure',
            '--generic' => false,
        ]);
    }
}
