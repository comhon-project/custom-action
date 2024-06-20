<?php

namespace Comhon\CustomAction\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;

class Controller extends BaseController
{
    protected function authorize(string $ability, ...$arguments)
    {
        return config('custom-action.use_policies')
            ? Gate::authorize($ability, ...$arguments)
            : true;
    }
}
