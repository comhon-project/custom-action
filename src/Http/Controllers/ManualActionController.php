<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomManualAction;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ManualActionController extends Controller
{
    /**
     * Display action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function show($actionType)
    {
        if (! CustomActionModelResolver::isAllowedAction($actionType)) {
            throw new NotFoundHttpException('not found');
        }
        $action = app(CustomActionModelResolver::getClass($actionType));
        $this->authorize('view', $action);

        $manualAction = CustomManualAction::with('actionSettings')->find($actionType);
        if (! $manualAction) {
            $manualAction = new CustomManualAction;
            $manualAction->type = $actionType;

            DB::transaction(function () use ($manualAction) {
                $settings = new CustomActionSettings;
                $settings->settings = [];
                $settings->save();
                $manualAction->actionSettings()->associate($settings);
                $manualAction->save();
            });
        }

        return new JsonResource($manualAction);
    }
}
