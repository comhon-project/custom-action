<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Bindings\Translatable;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Contracts\HasTranslatableBindingsInterface;
use Comhon\CustomAction\Facades\BindingsValidator;
use Illuminate\Support\Arr;

trait InteractWithBindingsTrait
{
    private $bindingsCache = [];

    private $validatedBindingsCache = [];

    /**
     * get validated bindings
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function getAllBindings(?string $locale = null, bool $useCache = false): array
    {
        if ($useCache && isset($this->bindingsCache[$locale])) {
            return $this->bindingsCache[$locale];
        }

        $bindings = [];
        if ($this instanceof CallableFromEventInterface) {
            $bindings = $this->getEventBindingsContainer()?->getBindingValues($locale) ?? [];
        }
        if ($this instanceof HasBindingsInterface) {
            $bindings = array_merge($bindings, $this->getBindingValues($locale));
        }

        if ($useCache) {
            $this->bindingsCache[$locale] = $bindings;
        }

        return $bindings;
    }

    /**
     * get validated bindings
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function getAllValidatedBindings(?string $locale = null, bool $useCache = false): array
    {
        if ($useCache && isset($this->validatedBindingsCache[$locale])) {
            return $this->validatedBindingsCache[$locale];
        }
        $bindings = [];

        if ($this instanceof CallableFromEventInterface) {
            $bindingsContainer = $this->getEventBindingsContainer();
            $bindings = $bindingsContainer?->getBindingValues($locale) ?? [];
            $schemaFromContainer = $bindingsContainer?->getBindingSchema();
            if ($schemaFromContainer !== null) {
                $bindings = BindingsValidator::getValidatedBindings($bindings, $schemaFromContainer);
            }
        }

        if ($this instanceof HasBindingsInterface) {
            $bindingsFromAction = $this->getBindingValues($locale);
            $schemaFromAction = $this->getBindingSchema($this);
            if ($schemaFromAction !== null) {
                $bindingsFromAction = BindingsValidator::getValidatedBindings($bindingsFromAction, $schemaFromAction);
            }
            $bindings = array_merge($bindings, $bindingsFromAction);
        }

        if ($this instanceof HasTranslatableBindingsInterface) {
            $this->setTranslationValues($bindings, $this->getTranslatableBindings());
        }

        if ($useCache) {
            $this->validatedBindingsCache[$locale] = $bindings;
        }

        return $bindings;
    }

    public static function setTranslationValues(array &$bindings, array $translationKeys)
    {
        $retrieveBindingRecursive = function (&$bindings, $path, $level, $prefix) use (&$retrieveBindingRecursive) {
            $currentKey = $path[$level] ?? null;
            if (! isset($currentKey)) {
                $bindings = new Translatable($bindings, $prefix);
            }
            if (! Arr::accessible($bindings)) {
                return;
            }
            if ($currentKey == '*') {
                foreach ($bindings as &$subValue) {
                    $retrieveBindingRecursive($subValue, $path, $level + 1, $prefix);
                }
            } elseif (Arr::exists($bindings, $currentKey)) {
                $retrieveBindingRecursive($bindings[$currentKey], $path, $level + 1, $prefix);
            }
        };

        foreach ($translationKeys as $bindingKey => $prefix) {
            $retrieveBindingRecursive($bindings, explode('.', $bindingKey), 0, $prefix);
        }
    }
}
