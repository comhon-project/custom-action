<?php

namespace Comhon\CustomAction\Commands;

use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\HandleManualActionTrait;
use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Bindings\EventBindingsContainer;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateActionCommand extends Command
{
    public $signature = 'custom-action:generate {name} {--extends=} {--manual} {--event} {--has-bindings}';

    public $description = 'generate a custom action class';

    private $withoutEventConstructor = <<<'EOT'
    public function __construct(protected Action $action) {}


EOT;

    private $withEventConstructor = <<<'EOT'
    public function __construct(
        protected Action $action,
        protected ?EventBindingsContainer $eventBindingsContainer = null,
    ) {}


EOT;

    public function handle(): int
    {
        $name = $this->argument('name');
        $extendsArg = $this->option('extends');

        $directory = app()->path('Actions'.DIRECTORY_SEPARATOR.'CustomActions');
        $imports = [];

        $extends = '';
        $extendsClass = null;

        $implements = [];
        $interfaces = [
            CustomActionInterface::class,
        ];

        $uses = [];
        $traits = [
            Dispatchable::class,
            Queueable::class,
            InteractsWithQueue::class,
            SerializesModels::class,
            InteractWithBindingsTrait::class,
            InteractWithSettingsTrait::class,
        ];

        $construct = '';

        if ($this->option('manual')) {
            $traits[] = HandleManualActionTrait::class;
        }
        if ($this->option('event')) {
            $interfaces[] = CallableFromEventInterface::class;
            $traits[] = CallableFromEventTrait::class;
        }
        if ($this->option('has-bindings')) {
            $interfaces[] = HasBindingsInterface::class;
        }

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

        if ($extendsClass && is_subclass_of($extendsClass, CustomActionInterface::class)) {
            $returnSettingsSchema = 'parent::getSettingsSchema($eventClassContext)';
            $returnLocalizedSettingsSchema = 'parent::getLocalizedSettingsSchema($eventClassContext)';
        }

        if (! $extendsClass || ! is_subclass_of($extendsClass, ShouldQueue::class)) {
            $imports[] = ShouldQueue::class;
        }

        $parentTraits = $extendsClass ? class_uses_recursive($extendsClass) : [];
        foreach ($traits as $trait) {
            if (! $extendsClass || ! in_array($trait, $parentTraits)) {
                $imports[] = $trait;
                $explode = explode('\\', $trait);
                $uses[] = $explode[count($explode) - 1];
            }
        }

        foreach ($interfaces as $interface) {
            if (! $extendsClass || ! is_subclass_of($extendsClass, $interface)) {
                $imports[] = $interface;
                $explode = explode('\\', $interface);
                $implements[] = $explode[count($explode) - 1];
            }
        }

        $hasConstruct = ($extendsClass && method_exists($extendsClass, '__construct'))
            || in_array(CallableFromEventTrait::class, $traits)
            || in_array(CallableFromEventTrait::class, $parentTraits);

        if (! $hasConstruct) {
            $imports[] = Action::class;

            if ($this->option('event') || ($extendsClass && is_subclass_of($extendsClass, CallableFromEventInterface::class))) {
                $imports[] = EventBindingsContainer::class;
                $construct = $this->withEventConstructor;
            } else {
                $construct = $this->withoutEventConstructor;
            }
        }

        $imports = collect($imports)->sort()->map(fn ($import) => "use $import;")->implode(PHP_EOL);
        $implements = count($implements) ? ' implements '.collect($implements)->implode(', ') : '';
        $uses = count($uses)
            ? '    use '.collect($uses)->implode(','.PHP_EOL.'        ').';'.PHP_EOL.PHP_EOL
            : '';

        $fileContent = <<<EOT
<?php

declare(strict_types=1);

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
