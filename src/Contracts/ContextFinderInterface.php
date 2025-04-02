<?php

namespace Comhon\CustomAction\Contracts;

interface ContextFinderInterface
{
    public function find(string $type, array $contextSchema): array;
}
