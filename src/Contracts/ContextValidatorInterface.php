<?php

namespace Comhon\CustomAction\Contracts;

interface ContextValidatorInterface
{
    /**
     * validate context
     *
     * @return array return validated context
     */
    public function getValidatedContext(array $context, array $schemaContext): array;
}
