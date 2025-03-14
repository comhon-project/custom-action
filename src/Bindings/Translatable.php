<?php

namespace Comhon\CustomAction\Bindings;

class Translatable
{
    public function __construct(public int|string|null $value, private \Closure|string|null $translator) {}

    public function translate()
    {
        return $this->translator instanceof \Closure
            ? ($this->translator)($this->value, app()->getLocale())
            : __($this->translator.$this->value);
    }

    public function __toString()
    {
        return $this->value;
    }
}
