<?php

namespace Comhon\CustomAction\Bindings;

class Translatable
{
    public int|string|null $value;

    public function __construct(int|string|Translatable|null $value, private \Closure|string|null $translator)
    {
        $this->value = $value instanceof Translatable ? $value->value : $value;
    }

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
