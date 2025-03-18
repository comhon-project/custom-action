<?php

namespace Comhon\CustomAction\Contracts;

interface HasTranslatableBindingsInterface
{
    /**
     * Get translation keys of translatable bindings
     */
    public static function getTranslatableBindings(): array;
}
