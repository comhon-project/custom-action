<?php

namespace Comhon\CustomAction\Commands;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Illuminate\Console\Command;

class GenerateActionCommand extends Command
{
    public $signature = 'custom-action:generate {name} {--extends=} {--generic}';

    public $description = 'generate a custom action class';

    public function handle(): int
    {
        $name = $this->argument('name');
        $extendsArg = $this->option('extends');
        $generic = $this->option('generic');

        $directory = app()->path('Console/Commands');
        $useExtends = null;
        switch ($extendsArg) {
            case 'send-email':
                $useExtends = SendTemplatedMail::class;
                break;
            default:
                throw new \Exception("invalid extends parameter '{$extendsArg}'");
        }
        $explode = explode('\\', $useExtends);
        $extends = $explode[count($explode) - 1];

        $useInterface = $generic ? CustomActionInterface::class : CustomUniqueActionInterface::class;
        $explode = explode('\\', $useInterface);
        $implements = $explode[count($explode) - 1];

        $fileContent = <<<EOT
<?php

namespace App\Console\Commands;

use $useExtends;
use $useInterface;

class $name extends $extends implements $implements
{
    public function getBindingSchema(): array
    {
        return [
            ...parent::getBindingSchema(),
            // Here goes your specific action bindings
        ];
    }
}

EOT;

        if (! file_exists($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents("$directory/$name.php", $fileContent);

        return self::SUCCESS;
    }
}
