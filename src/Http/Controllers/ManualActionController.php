<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\ManualAction;
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

        $manualAction = ManualAction::with('actionSettings')->find($actionType);
        if (! $manualAction) {
            $manualAction = new ManualAction;
            $manualAction->type = $actionType;

            DB::transaction(function () use ($manualAction) {
                $settings = new ActionSettings;
                $settings->settings = [];
                $settings->save();
                $manualAction->actionSettings()->associate($settings);
                $manualAction->save();
            });
        }

        return new JsonResource($manualAction);
    }
}
