<?php

namespace Comhon\CustomAction\Resolver;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\ModelResolverContract\ModelResolverInterface;

/**
 * @method void bind(string $uniqueName, string $class)
 * @method ?string getUniqueName(string $class)
 * @method ?string getClass(string $uniqueName)
 * @method \Comhon\ModelResolverContract\ModelResolverInterface getResolver()
 * @method void isAllowedAction(string $uniqueName)
 * @method void isAllowedEvent(string $uniqueName)
 */
class CustomActionModelResolver
{
    public function __construct(private ModelResolverInterface $resolver) {}

    /**
     * get model resolver instance
     */
    public function getResolver(): ModelResolverInterface
    {
        return $this->resolver;
    }

    /**
     * verify if model is allowed in 'custom-generic-action' or 'custom-unique-action' scope
     */
    public function isAllowedAction(string $uniqueName): bool
    {
        return is_subclass_of($this->resolver->getClass($uniqueName), CustomActionInterface::class);
    }

    /**
     * verify if model is allowed in 'custom-event' scope
     */
    public function isAllowedEvent(string $uniqueName): bool
    {
        return is_subclass_of($this->resolver->getClass($uniqueName), CustomEventInterface::class);
    }

    /**
     * Dynamically call the model resolver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->resolver->$method(...$parameters);
    }
}
