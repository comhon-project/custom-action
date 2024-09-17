<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
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
        $actionClass = CustomActionModelResolver::getClass($actionType);
        $this->authorize('view', [CustomActionInterface::class, $actionClass]);

        $manualAction = ManualAction::with('actionSettings')->find($actionType);
        if (! $manualAction) {
            $manualAction = new ManualAction;
            $manualAction->type = $actionType;

            DB::transaction(function () use ($manualAction) {
                $manualAction->save();

                $actionSettings = new ActionSettings;
                $actionSettings->settings = [];
                $actionSettings->action()->associate($manualAction);
                $actionSettings->save();

                // to avoid infinite loop
                $actionSettings->unsetRelation('action');

                $manualAction->setRelation('actionSettings', $actionSettings);
            });
        }

        return new JsonResource($manualAction);
    }
}
