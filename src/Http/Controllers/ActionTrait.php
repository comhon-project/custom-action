<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\Action;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

trait ActionTrait
{
    protected function listActionScopedSettings(Request $request, Action $action): JsonResource
    {
        $this->authorize('view', $action);

        $query = $action->scopedSettings();

        $name = $request->input('name');
        if ($name !== null) {
            $query->where('name', 'LIKE', "%$name%");
        }

        return new JsonResource($query->select('id', 'name')->paginate());
    }
}
