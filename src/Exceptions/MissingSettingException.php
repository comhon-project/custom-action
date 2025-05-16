<?php

namespace Comhon\CustomAction\Exceptions;

use Comhon\CustomAction\Models\Action;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissingSettingException extends \Exception
{
    public function __construct(public Action $action, bool $default)
    {
        $actionClass = get_class($action);
        $setting = $default ? 'default setting' : 'setting';
        $this->message = "missing $setting on action $actionClass with {$action->getKeyName()} '{$action->getKey()}'";
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 422);
    }
}
