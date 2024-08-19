<?php

namespace Comhon\CustomAction\Commands;

use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ActionSettings;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateActionCommand extends Command
{
    public $signature = 'custom-action:generate {name} {--extends=}';

    public $description = 'generate a custom action class';

    public function handle(): int
    {
        $name = $this->argument('name');
        $extendsArg = $this->option('extends');

        $directory = app()->path('Actions'.DIRECTORY_SEPARATOR.'CustomActions');
        $imports = [];
        $extends = '';
        $implements = '';
        $uses = [];
        $construct = '';
        $extendsClass = null;

        $returnSettingsSchema = '[]';
        $returnLocalizedSettingsSchema = '[]';

        if ($extendsArg) {
            $extendsClass = CustomActionModelResolver::getClass($extendsArg);
            if (! $extendsClass) {
                throw new \Exception("invalid extends parameter '{$extendsArg}'");
            }
            $imports[] = $extendsClass;
            $explode = explode('\\', $extendsClass);
            $extends = ' extends '.$explode[count($explode) - 1];
        }

        if (! $extendsClass || ! is_subclass_of($extendsClass, CustomActionInterface::class)) {
            $imports[] = CustomActionInterface::class;
            $explode = explode('\\', CustomActionInterface::class);
            $implements = ' implements '.$explode[count($explode) - 1];
        } else {
            $returnSettingsSchema = 'parent::getSettingsSchema($eventClassContext)';
            $returnLocalizedSettingsSchema = 'parent::getLocalizedSettingsSchema($eventClassContext)';
        }

        if (! $extendsClass || ! is_subclass_of($extendsClass, ShouldQueue::class)) {
            $imports[] = ShouldQueue::class;
        }

        if (! $extendsClass || ! in_array(Queueable::class, class_uses_recursive($extendsClass))) {
            $imports[] = Queueable::class;
            $explode = explode('\\', Queueable::class);
            $uses[] = $explode[count($explode) - 1];
        }

        if (! $extendsClass || ! method_exists($extendsClass, '__construct')) {
            $imports[] = ActionSettings::class;
            $imports[] = BindingsContainerInterface::class;

            $construct = <<<'EOT'
    public function __construct(
        private ActionSettings $actionSettings,
        private ?BindingsContainerInterface $bindingsContainer = null,
    ) {
        //
    }


EOT;
        }

        $imports = collect($imports)->sort()->map(fn ($import) => "use $import;")->implode(PHP_EOL);
        $uses = ! empty($uses)
            ? '    use '.collect($uses)->sort()->implode(', ').';'.PHP_EOL.PHP_EOL
            : '';

        $fileContent = <<<EOT
<?php

namespace App\Actions\CustomActions;

$imports

class {$name}{$extends}{$implements}
{
{$uses}{$construct}    /**
     * Get action settings schema
     */
    public static function getSettingsSchema(?string \$eventClassContext = null): array
    {
        return $returnSettingsSchema;
    }

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string \$eventClassContext = null): array
    {
        return $returnLocalizedSettingsSchema;
    }


    /**
     * execute action
     */
    public function handle(): void
    {
        //
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
