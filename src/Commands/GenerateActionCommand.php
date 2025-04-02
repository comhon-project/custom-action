<?php

namespace Comhon\CustomAction\Commands;

use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\CallableManually;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasContextInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
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
        {--has-context : Whether the action has context}
        {--has-translatable-context : Whether the action has translatable context}';

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
            InteractWithContextTrait::class,
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
        if ($this->option('has-translatable-context')) {
            $interfaces[] = HasContextInterface::class;
            $interfaces[] = HasTranslatableContextInterface::class;
        } elseif ($this->option('has-context')) {
            $interfaces[] = HasContextInterface::class;
        }

        $extendsClass = $this->getExtendsClassFromOption();
        if ($extendsClass) {
            $imports[] = $extendsClass;
            $explode = explode('\\', $extendsClass);
            $extends = ' extends '.$explode[count($explode) - 1];
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

        $functions = $this->getFunctions($extendsClass, $interfaces);
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
            $functions,
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

    private function getFunctions(?string $extendsClass, array $interfaces): string
    {

        $functions = [
            'getSettingsSchema' => [
                'static' => true,
                'params' => ['$eventClassContext' => [
                    'type' => '?string',
                    'default' => 'null',
                ]],
                'return' => 'array',
            ],
            'getLocalizedSettingsSchema' => [
                'static' => true,
                'params' => ['$eventClassContext' => [
                    'type' => '?string',
                    'default' => 'null',
                ]],
                'return' => 'array',
            ],
        ];
        if (in_array(HasContextInterface::class, $interfaces)) {
            $functions = [
                ...$functions,
                'getContextSchema' => [
                    'static' => true,
                    'return' => 'array',
                ],
                'getContext' => [
                    'return' => 'array',
                ],
            ];
        }
        if (in_array(HasTranslatableContextInterface::class, $interfaces)) {
            $functions = [
                ...$functions,
                'getTranslatableContext' => [
                    'static' => true,
                    'return' => 'array',
                ],
            ];
        }
        if (! $extendsClass || (! method_exists($extendsClass, 'handle') && ! method_exists($extendsClass, '__invoke'))) {
            $functions['handle'] = [];
        }

        $stringifiedFunctions = [];
        foreach ($functions as $function => $schemaFunc) {
            $static = ($schemaFunc['static'] ?? false) ? 'static ' : '';
            $returnType = ($schemaFunc['return'] ?? null) ? ": {$schemaFunc['return']}" : '';

            $stringifiedParams = [];
            foreach ($schemaFunc['params'] ?? [] as $param => $schemaParam) {
                $type = ($schemaParam['type'] ?? null) ? "{$schemaParam['type']} " : '';
                $default = ($schemaParam['default'] ?? null) ? " = {$schemaParam['default']}" : '';
                $stringifiedParams[] = "{$type}{$param}{$default}";
            }
            $stringifiedParams = implode(', ', $stringifiedParams);

            $return = ($schemaFunc['return'] ?? null) == 'array' ? ' []' : '';
            if ($extendsClass && method_exists($extendsClass, $function)) {
                $params = implode(', ', array_keys($schemaFunc['params'] ?? []));
                $return = " parent::{$function}({$params})";
            }

            $stringifiedFunctions[] = <<<EOT
                public {$static}function {$function}($stringifiedParams)$returnType
                {
                    return{$return};
                }
            EOT;
        }

        return implode(PHP_EOL.PHP_EOL, $stringifiedFunctions);
    }

    private function generateFileContent(
        $imports,
        $name,
        $extends,
        $implements,
        $uses,
        $functions,
    ): string {
        return <<<EOT
        <?php
        
        declare(strict_types=1);
        
        namespace App\Actions\CustomActions;
        
        $imports
        
        class {$name}{$extends}{$implements}
        {
        {$uses}{$functions}
        }
        
        EOT;
    }
}
