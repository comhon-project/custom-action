<?php

namespace Comhon\CustomAction\Commands;

use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\CallableManually;
use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateActionCommand extends Command
{
    private const INVOKABLE_METHODS = [
        'manually' => 'manually',
        'from-event' => 'from event',
    ];

    public $signature = 'custom-action:generate
        {name : The class name of your action}
        {--callable= : How your action will be called (manually, from-event)}
        {--extends= : The action (class name or unique name) that must be extended}
        {--has-bindings : Whether the action has bindings}';

    public $description = 'generate a custom action class';

    public function handle(): int
    {
        $name = $this->argument('name');

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

        $callable = $this->getCallableFromOption();
        $callableManually = $callable == 'manually';

        if ($callableManually) {
            $traits[] = CallableManually::class;
        } else {
            $interfaces[] = CallableFromEventInterface::class;
            $traits[] = CallableFromEventTrait::class;
        }
        if ($this->option('has-bindings')) {
            $interfaces[] = HasBindingsInterface::class;
        }

        $returnSettingsSchema = '[]';
        $returnLocalizedSettingsSchema = '[]';

        $extendsClass = $this->getExtendsClassFromOption();
        if ($extendsClass) {
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

        $imports = collect($imports)->sort()->map(fn ($import) => "use $import;")->implode(PHP_EOL);
        $implements = count($implements) ? ' implements '.collect($implements)->implode(', ') : '';
        $uses = count($uses)
            ? '    use '.collect($uses)->implode(','.PHP_EOL.'        ').';'.PHP_EOL.PHP_EOL
            : '';

        $fileContent = $this->generateFileContent(
            $imports,
            $name,
            $extends,
            $implements,
            $uses,
            $returnSettingsSchema,
            $returnLocalizedSettingsSchema,
        );

        if (! file_exists($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents("$directory/$name.php", $fileContent);

        return self::SUCCESS;
    }

    private function getCallableFromOption(): string
    {
        $callable = $this->option('callable');
        if (! isset(self::INVOKABLE_METHODS[$callable])) {
            if ($callable) {
                $this->error("invalid input callable '{$callable}'");
            }
            $input = $this->choice(
                'How will you invoke your action?',
                array_values(self::INVOKABLE_METHODS),
            );
            $callable = array_search($input, self::INVOKABLE_METHODS);
            if ($callable === false) {
                throw new \Exception("invalid input callable '$input'");
            }
        }

        return $callable;
    }

    private function getExtendsClassFromOption(): ?string
    {
        $extends = $this->option('extends');
        if (! $extends) {
            return null;
        }
        $nameSpaces = [
            'App\Actions\CustomActions',
            'App\Actions',
            'Comhon\CustomAction\Actions',
            '',
        ];
        foreach ($nameSpaces as $nameSpace) {
            $class = $nameSpace.'\\'.$extends;
            if (class_exists($class)) {
                return $class;
            }
        }

        $extendsClass = CustomActionModelResolver::getClass($extends);
        if ($extendsClass) {
            return $extendsClass;
        }

        throw new \Exception("invalid extends parameter '{$extends}'");
    }

    private function generateFileContent(
        $imports,
        $name,
        $extends,
        $implements,
        $uses,
        $returnSettingsSchema,
        $returnLocalizedSettingsSchema,
    ): string {
        return <<<EOT
        <?php
        
        declare(strict_types=1);
        
        namespace App\Actions\CustomActions;
        
        $imports
        
        class {$name}{$extends}{$implements}
        {
        {$uses}    /**
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
    }
}
