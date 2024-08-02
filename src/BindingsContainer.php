<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Contracts\BindingsContainerInterface;

class BindingsContainer implements BindingsContainerInterface
{
    public function __construct(private $bindingValues, private ?array $bindingSchema = null) {}

    /**
     * Get event binding values
     */
    public function getBindingValues(?string $locale = null): array
    {
        $bindingValues = $this->bindingValues;

        return $bindingValues instanceof \Closure ? $bindingValues($locale) : $bindingValues;
    }

    /**
     * Get event binding schema
     */
    public function getBindingSchema(): ?array
    {
        return $this->bindingSchema;
    }
}
