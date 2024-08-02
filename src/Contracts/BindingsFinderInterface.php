<?php

namespace Comhon\CustomAction\Contracts;

interface BindingsFinderInterface
{
    public function find(string $bindingType, array $bindingSchema): array;
}
