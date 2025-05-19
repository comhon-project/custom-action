<?php

namespace App\Actions;

use Comhon\CustomAction\Contracts\SimulatableInterface;

class BadAction implements SimulatableInterface
{
    public static function buildFakeInstance(): static
    {
        return new static;
    }
}
