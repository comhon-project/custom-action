<?php

namespace Comhon\CustomAction\Contracts;

interface BindingsContainerInterface
{
    /**
     * Get event binding values
     */
    public function getBindingValues(?string $locale = null): array;

    /**
     * Get event binding schema
     */
    public function getBindingSchema(): ?array;
}
