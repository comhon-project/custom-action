<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Catalogs\ManualActionTypeCatalog;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ManualActionController extends Controller
{
    use ActionTrait;

    /**
     * Display a listing of actions.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function listTypes(ManualActionTypeCatalog $catalog)
    {
        $this->authorize('view-any', ManualAction::class);

        return new JsonResource($catalog->get());
    }

    /**
     * Display action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function show(string $type)
    {
        $manualAction = $this->getOrCreateManualAction($type);
        $this->authorize('view', $manualAction);

        return new JsonResource($manualAction);
    }

    public function listActionScopedSettings(Request $request, string $type)
    {
        $manualAction = $this->getOrCreateManualAction($type);

        return $this->listCommonActionScopedSettings($request, $manualAction);
    }

    public function storeDefaultSettings(Request $request, ActionService $actionService, string $type): JsonResource
    {
        $manualAction = $this->getOrCreateManualAction($type);
        $this->authorize('create', [ActionSettings::class, $manualAction]);

        $defaultSettings = $actionService->storeDefaultSettings($manualAction, $request->input());

        return new JsonResource($defaultSettings);
    }

    public function storeScopedSettings(Request $request, ActionService $actionService, string $type): JsonResource
    {
        $manualAction = $this->getOrCreateManualAction($type);
        $this->authorize('create', [ActionScopedSettings::class, $manualAction]);

        $defaultSettings = $actionService->storeScopedSettings($manualAction, $request->input());

        return new JsonResource($defaultSettings);
    }

    private function getOrCreateManualAction(string $type): ManualAction
    {
        if (! CustomActionModelResolver::isAllowedAction($type)) {
            throw new NotFoundHttpException('not found');
        }

        $manualAction = ManualAction::with('actionSettings')->find($type);
        if (! $manualAction) {
            $manualAction = new ManualAction;
            $manualAction->type = $type;
            $manualAction->save();

            $manualAction->setRelation('actionSettings', null);
        }

        return $manualAction;
    }
}
