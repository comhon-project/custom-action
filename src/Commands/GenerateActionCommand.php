<?php

namespace Comhon\CustomAction\Commands;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Console\Command;

class GenerateActionCommand extends Command
{
    public $signature = 'custom-action:generate {name} {--extends=}';

    public $description = 'generate a custom action class';

    public function handle(): int
    {
        $name = $this->argument('name');
        $extendsArg = $this->option('extends');

        $directory = app()->path('Actions'.DIRECTORY_SEPARATOR.'CustomActions');
        $uses = [];
        $extends = '';
        $implements = '';

        if ($extendsArg) {
            $useExtends = CustomActionModelResolver::getClass($extendsArg);
            if (! $useExtends) {
                throw new \Exception("invalid extends parameter '{$extendsArg}'");
            }
            $uses[] = $useExtends;
            $explode = explode('\\', $useExtends);
            $extends = ' extends '.$explode[count($explode) - 1];
        }
        if (empty($uses) || ! is_subclass_of($uses[0], CustomActionInterface::class)) {
            $uses[] = CustomActionInterface::class;
            $explode = explode('\\', CustomActionInterface::class);
            $implements = ' implements '.$explode[count($explode) - 1];
        }

        $uses = collect($uses)->map(fn ($use) => "use $use;")->implode("\n");

        $fileContent = <<<EOT
<?php

namespace App\Actions\CustomActions;

$uses

class {$name}{$extends}{$implements}
{

}

EOT;

        if (! file_exists($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents("$directory/$name.php", $fileContent);

        return self::SUCCESS;
    }
}
