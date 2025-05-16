<?php

namespace Comhon\CustomAction\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimulateActionException extends \Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 422);
    }
}
