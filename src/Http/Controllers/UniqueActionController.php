<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomUniqueAction;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UniqueActionController extends Controller
{
    /**
     * Display action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function show($uniqueActionType)
    {
        $uniqueAction = CustomUniqueAction::query()->find($uniqueActionType);
        if (! $uniqueAction) {
            if (! CustomActionModelResolver::isAllowedAction($uniqueActionType)) {
                throw new NotFoundHttpException('not found');
            }

            $uniqueAction = new CustomUniqueAction();
            $uniqueAction->type = $uniqueActionType;

            DB::transaction(function () use ($uniqueAction) {
                $settings = new CustomActionSettings();
                $settings->settings = [];
                $settings->save();
                $uniqueAction->actionSettings()->associate($settings);
                $uniqueAction->save();
            });
        }
        $this->authorize('view', $uniqueAction->actionSettings);

        return new JsonResource($uniqueAction);
    }
}
