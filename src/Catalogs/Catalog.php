<?php

namespace Comhon\CustomAction\Catalogs;

abstract class Catalog
{
    final public function __construct(private array|\Closure $getter) {}

    final public function get(): array
    {
        return is_array($this->getter) ? $this->getter : ($this->getter)();
    }
}
