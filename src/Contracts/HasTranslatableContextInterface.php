<?php

namespace Comhon\CustomAction\Contracts;

interface HasTranslatableContextInterface
{
    /**
     * Get translation keys of translatable context
     */
    public static function getTranslatableContext(): array;
}
