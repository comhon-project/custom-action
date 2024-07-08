<?php

namespace Comhon\CustomAction\Contracts;

interface BindingFinderInterface
{
    public function find(string $bindingType, array $bindingSchema): array;
}
