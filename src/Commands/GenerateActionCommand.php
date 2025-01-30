<?php

namespace Comhon\CustomAction\Commands;

use Comhon\CustomAction\Actions\HandleManualActionTrait;
use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithLocalizedSettingsTrait;
use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateActionCommand extends Command
{
    public $signature = 'custom-action:generate {name} {--extends=} {--manual}';

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

        $traits = $extendsClass ? class_uses_recursive($extendsClass) : [];
        $actionTraits = [
            Dispatchable::class,
            Queueable::class,
            InteractsWithQueue::class,
            SerializesModels::class,
            InteractWithBindingsTrait::class,
            InteractWithLocalizedSettingsTrait::class,
        ];
        if ($this->option('manual')) {
            $actionTraits[] = HandleManualActionTrait::class;
        }
        foreach ($actionTraits as $actionTrait) {
            if (! $extendsClass || ! in_array($actionTrait, $traits)) {
                $imports[] = $actionTrait;
                $explode = explode('\\', $actionTrait);
                $uses[] = $explode[count($explode) - 1];
            }
        }

        if (! $extendsClass || ! method_exists($extendsClass, '__construct')) {
            $imports[] = Setting::class;
            $imports[] = BindingsContainerInterface::class;

            $construct = <<<'EOT'
    public function __construct(
        protected Setting $setting,
        protected ?BindingsContainerInterface $bindingsContainer = null,
    ) {
        //
    }


EOT;
        }

        $imports = collect($imports)->sort()->map(fn ($import) => "use $import;")->implode(PHP_EOL);
        $uses = ! empty($uses)
            ? '    use '.collect($uses)->implode(','.PHP_EOL.'        ').';'.PHP_EOL.PHP_EOL
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
