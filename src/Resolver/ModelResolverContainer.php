<?php

namespace Comhon\CustomAction\Resolver;

use Comhon\ModelResolverContract\ModelResolverInterface;

/**
 * @method void register(array $bindings, array $scopes = [])
 * @method ?string getUniqueName(string $class)
 * @method ?string getClass(string $uniqueName)
 * @method bool isAllowed(string $uniqueName, string $scope)
 * @method array getUniqueNames(string $scope)
 * @method array getClasses(string $scope)
 * 
 */
class ModelResolverContainer
{
    const GENERIC_ACTION_SCOPE = 'custom-generic-action';
    const UNIQUE_ACTION_SCOPE = 'custom-unique-action';
    const EVENT_SCOPE = 'custom-event';

    public function __construct(private ModelResolverInterface $resolver)
    {
    }

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
        return $this->resolver->isAllowed($uniqueName, self::GENERIC_ACTION_SCOPE)
            ?: $this->resolver->isAllowed($uniqueName, self::UNIQUE_ACTION_SCOPE);
    }

    /**
     * verify if model is allowed in 'custom-event' scope
     */
    public function isAllowedEvent(string $uniqueName): bool
    {
        return $this->resolver->isAllowed($uniqueName, self::EVENT_SCOPE);
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
