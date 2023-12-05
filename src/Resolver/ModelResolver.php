<?php

namespace Comhon\CustomAction\Resolver;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\ModelResolverContract\ModelResolverInterface;

/**
 * A basic implementation of a model resolver
 */
class ModelResolver implements ModelResolverInterface
{
    private $map = [
        'send-email' => SendTemplatedMail::class,
    ];

    private $scopes = [
        ModelResolverContainer::GENERIC_ACTION_SCOPE => ['send-email'],
    ];

    /**
     * register model bindings and scopes
     */
    public function register(array $bindings, array $scopes = [])
    {
        $this->map = $bindings;
        $this->scopes = $scopes;
    }

    /**
     * get model unique name according given class
     */
    public function getUniqueName(string $class): ?string
    {
        return array_search($class, $this->map) ?: null;
    }

    /**
     * get model class according unique name
     */
    public function getClass(string $uniqueName): ?string
    {
        return $this->map[$uniqueName] ?? null;
    }

    /**
     * verify if model is allowed in given scope
     */
    public function isAllowed(string $uniqueName, string $scope): bool
    {
        return isset($this->scopes[$scope]) && in_array($uniqueName, $this->scopes[$scope]);
    }

    /**
     * get all models unique names allowed in given scope
     */
    public function getUniqueNames(string $scope): array
    {
        return $this->scopes[$scope] ?? [];
    }

    /**
     * get all models classes allowed in given scope
     */
    public function getClasses(string $scope): array
    {
        if (! isset($this->scopes[$scope])) {
            return [];
        }
        $classes = [];
        foreach ($this->scopes[$scope] as $uniqueName) {
            $classes[] = $this->getClass($uniqueName);
        }

        return $classes;
    }
}
