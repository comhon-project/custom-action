<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Context\ContextHelper;
use Comhon\CustomAction\Context\Translatable;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

trait InteractWithContextTrait
{
    private $contextCache = [];

    /**
     * get context from action and from event if action is triggered from event
     *
     * @param  bool  $useCache  if true, cache context for the action instance,
     *                          and get value from it if exists.
     */
    public function getExposedContext(bool $withTranslations = false, bool $validated = false, bool $useCache = true): array
    {
        if ($useCache && isset($this->contextCache[$withTranslations][$validated])) {
            return $this->contextCache[$withTranslations][$validated];
        }

        $contextObjects = [];
        if ($this instanceof CallableFromEventInterface && $this->getEvent() instanceof ExposeContextInterface) {
            $contextObjects[] = $this->getEvent();
        }
        if ($this instanceof ExposeContextInterface) {
            $contextObjects[] = $this;
        }

        $context = [];
        foreach ($contextObjects as $contextObject) {
            $currentContext = ContextHelper::extractContext($contextObject);
            if ($validated) {
                $currentContext = Validator::validate($currentContext, $contextObject->getContextSchema($this));
            }
            if ($withTranslations && $contextObject instanceof HasTranslatableContextInterface) {
                $this->setTranslationValues($currentContext, $contextObject->getTranslatableContext($this));
            }
            // for the merge, action context takes priority over event context.
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
    public function getExposedValidatedContext(bool $withTranslations = false, bool $useCache = true): array
    {
        return $this->getExposedContext($withTranslations, true, $useCache);
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
