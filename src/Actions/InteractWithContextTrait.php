<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Context\ContextHelper;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
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

        $context = [];
        foreach ($this->getContextObjects() as $contextObject) {
            $currentContext = ContextHelper::extractContext($contextObject);
            if ($validated) {
                $currentContext = Validator::validate($currentContext, $contextObject->getContextSchema($this));
            }
            if ($withTranslations && $contextObject instanceof HasTranslatableContextInterface) {
                ContextHelper::setTranslationValues($currentContext, $contextObject->getTranslatableContext($this));
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
     * Get the objects that expose the context: the event (if action is triggered from event), then the action.
     *
     * @return ExposeContextInterface[]
     */
    protected function getContextObjects(): array
    {
        $contextObjects = [];
        if ($this instanceof CallableFromEventInterface && $this->getEvent() instanceof ExposeContextInterface) {
            $contextObjects[] = $this->getEvent();
        }
        if ($this instanceof ExposeContextInterface) {
            $contextObjects[] = $this;
        }

        return $contextObjects;
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
}
