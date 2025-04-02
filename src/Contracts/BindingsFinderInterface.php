<?php

namespace Comhon\CustomAction\Contracts;

interface BindingsFinderInterface
{
    public function find(string $type, array $contextSchema): array;
}
