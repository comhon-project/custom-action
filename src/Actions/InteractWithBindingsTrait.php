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

    /**
     * get bindings from action and from event if action is triggered from event
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function getAllBindings(bool $withTranslations = false, bool $validated = false, bool $useCache = true): array
    {
        if ($useCache && isset($this->bindingsCache[$withTranslations][$validated])) {
            return $this->bindingsCache[$withTranslations][$validated];
        }

        $hasBindingsObjects = [];
        if ($this instanceof CallableFromEventInterface && $this->getEvent() instanceof HasBindingsInterface) {
            $hasBindingsObjects[] = $this->getEvent();
        }
        if ($this instanceof HasBindingsInterface) {
            $hasBindingsObjects[] = $this;
        }

        $bindings = [];
        foreach ($hasBindingsObjects as $hasBindings) {
            $currentBindings = $validated
                ? BindingsValidator::getValidatedBindings(
                    $hasBindings->getContext(),
                    $hasBindings->getBindingSchema($this)
                )
                : $hasBindings->getContext();

            if ($withTranslations && $hasBindings instanceof HasTranslatableBindingsInterface) {
                $this->setTranslationValues($currentBindings, $hasBindings->getTranslatableBindings($this));
            }
            $bindings = empty($bindings) ? $currentBindings : array_merge($bindings, $currentBindings);
        }

        if ($useCache) {
            $this->bindingsCache[$withTranslations][$validated] = $bindings;
        }

        return $bindings;
    }

    /**
     * get bindings from action and from event if action is triggered from event
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function getAllValidatedBindings(bool $withTranslations = false, bool $useCache = true): array
    {
        return $this->getAllBindings($withTranslations, true, $useCache);
    }

    public static function setTranslationValues(array &$values, array $translations)
    {
        $accessValueRecursive = function (&$values, $path, $level, $translator) use (&$accessValueRecursive) {
            $currentKey = $path[$level] ?? null;
            if (! isset($currentKey)) {
                $values = new Translatable($values, $translator);
            }
            if (! Arr::accessible($values)) {
                return;
            }
            if ($currentKey == '*') {
                foreach ($values as &$subValue) {
                    $accessValueRecursive($subValue, $path, $level + 1, $translator);
                }
            } elseif (Arr::exists($values, $currentKey)) {
                $accessValueRecursive($values[$currentKey], $path, $level + 1, $translator);
            }
        };

        foreach ($translations as $key => $translator) {
            $accessValueRecursive($values, explode('.', $key), 0, $translator);
        }
    }
}
