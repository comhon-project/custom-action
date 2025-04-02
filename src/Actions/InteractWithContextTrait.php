<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Context\Translatable;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\HasContextInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Facades\ContextValidator;
use Illuminate\Support\Arr;

trait InteractWithContextTrait
{
    private $contextCache = [];

    /**
     * get context from action and from event if action is triggered from event
     *
     * @param  bool  $useCache  if true, cache context for the action instance,
     *                          and get value from it if exists.
     */
    public function getAllContext(bool $withTranslations = false, bool $validated = false, bool $useCache = true): array
    {
        if ($useCache && isset($this->contextCache[$withTranslations][$validated])) {
            return $this->contextCache[$withTranslations][$validated];
        }

        $hasContextObjects = [];
        if ($this instanceof CallableFromEventInterface && $this->getEvent() instanceof HasContextInterface) {
            $hasContextObjects[] = $this->getEvent();
        }
        if ($this instanceof HasContextInterface) {
            $hasContextObjects[] = $this;
        }

        $context = [];
        foreach ($hasContextObjects as $hasContext) {
            $currentContext = $validated
                ? ContextValidator::getValidatedContext(
                    $hasContext->getContext(),
                    $hasContext->getContextSchema($this)
                )
                : $hasContext->getContext();

            if ($withTranslations && $hasContext instanceof HasTranslatableContextInterface) {
                $this->setTranslationValues($currentContext, $hasContext->getTranslatableContext($this));
            }
            $context = empty($context) ? $currentContext : array_merge($context, $currentContext);
        }

        if ($useCache) {
            $this->contextCache[$withTranslations][$validated] = $context;
        }

        return $context;
    }

    /**
     * get context from action and from event if action is triggered from event
     *
     * @param  bool  $useCache  if true, cache context for the action instance,
     *                          and get value from it if exists.
     */
    public function getAllValidatedContext(bool $withTranslations = false, bool $useCache = true): array
    {
        return $this->getAllContext($withTranslations, true, $useCache);
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
