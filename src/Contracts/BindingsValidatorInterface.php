<?php

namespace Comhon\CustomAction\Contracts;

interface BindingsValidatorInterface
{
    /**
     * validate bindings
     *
     * @return array return validated bindings
     */
    public function getValidatedBindings(array $bindings, array $schemaBindings): array;
}
