<?php

namespace Comhon\CustomAction\Bindings;

class Translatable
{
    public function __construct(private $value, private $prefix = '') {}

    public function translate()
    {
        return __($this->prefix.$this->value);
    }

    public function __toString()
    {
        return $this->value;
    }
}
