<?php

namespace Comhon\CustomAction\Contracts;

interface HasTranslatableBindingsInterface
{
    /**
     * Get translation keys of translatable bindings
     */
    public function getTranslatableBindings(): array;
}
