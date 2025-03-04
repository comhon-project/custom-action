<?php

namespace Comhon\CustomAction\Resolver;

use Comhon\CustomAction\Actions\QueueAutomaticEmail;
use Comhon\CustomAction\Actions\SendAutomaticEmail;
use Comhon\ModelResolverContract\ModelResolverInterface;

/**
 * A basic implementation of a model resolver
 */
class ModelResolver implements ModelResolverInterface
{
    private $map = [
        'send-automatic-email' => SendAutomaticEmail::class,
        'queue-automatic-email' => QueueAutomaticEmail::class,
    ];

    /**
     * register model bindings and scopes
     */
    public function register(array $bindings, bool $reset = false)
    {
        $this->map = $reset ? $bindings : array_merge($this->map, $bindings);
    }

    /**
     * Bind a unique name to a class.
     */
    public function bind(string $uniqueName, string $class)
    {
        $this->map[$uniqueName] = $class;
    }

    /**
     * Get unique name according given class.
     */
    public function getUniqueName(string $class): ?string
    {
        return array_search($class, $this->map) ?: null;
    }

    /**
     * Get class according given unique name.
     */
    public function getClass(string $uniqueName): ?string
    {
        return $this->map[$uniqueName] ?? null;
    }
}
