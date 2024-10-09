<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Facades\BindingsValidator;

trait InteractWithBindingsTrait
{
    private $bindingsCache = [];

    /**
     * get validated bindings
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function getValidatedBindings(?string $locale = null, bool $useCache = false): array
    {
        if ($useCache && isset($this->bindingsCache[$locale])) {
            return $this->bindingsCache[$locale];
        }

        $bindings = $this->bindingsContainer?->getBindingValues($locale) ?? [];
        $schemaFromContainer = $this->bindingsContainer?->getBindingSchema();
        if ($schemaFromContainer !== null) {
            $bindings = BindingsValidator::getValidatedBindings($bindings, $schemaFromContainer);
        }

        if ($this instanceof HasBindingsInterface) {
            $bindingsFromAction = $this->getBindingValues($locale);
            $schemaFromAction = $this->getBindingSchema();
            if ($schemaFromAction !== null) {
                $bindingsFromAction = BindingsValidator::getValidatedBindings($bindingsFromAction, $schemaFromAction);
            }
            $bindings = array_merge($bindings, $bindingsFromAction);
        }
        if ($useCache) {
            $this->bindingsCache[$locale] = $bindings;
        }

        return $bindings;
    }
}
