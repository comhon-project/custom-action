<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Catalogs\ManualActionTypeCatalog;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ManualActionController extends Controller
{
    use ActionTrait;

    /**
     * Display a listing of actions.
     */
    public function listTypes(ManualActionTypeCatalog $catalog)
    {
        $this->authorize('view-any', ManualAction::class);

        return new JsonResource($catalog->get());
    }

    /**
     * Display action settings.
     */
    public function show(string $type)
    {
        $manualAction = $this->getOrCreateManualAction($type);
        $this->authorize('view', $manualAction);

        return new JsonResource($manualAction);
    }

    public function listScopedSettings(Request $request, string $type)
    {
        $manualAction = $this->getOrCreateManualAction($type);

        return $this->listActionScopedSettings($request, $manualAction);
    }

    public function storeDefaultSetting(Request $request, ActionService $actionService, string $type): JsonResource
    {
        $manualAction = $this->getOrCreateManualAction($type);
        $this->authorize('create', [DefaultSetting::class, $manualAction]);

        $defaultSetting = $actionService->storeDefaultSetting($manualAction, $request->input());

        return new JsonResource($defaultSetting);
    }

    public function storeScopedSetting(Request $request, ActionService $actionService, string $type): JsonResource
    {
        $manualAction = $this->getOrCreateManualAction($type);
        $this->authorize('create', [ScopedSetting::class, $manualAction]);

        $scopedSetting = $actionService->storeScopedSetting($manualAction, $request->input());

        return new JsonResource($scopedSetting);
    }

    public function simulate(Request $request, ActionService $actionService, string $type)
    {
        $manualAction = $this->getOrCreateManualAction($type);
        $this->authorize('simulate', $manualAction);

        return new JsonResource($actionService->simulate($manualAction, $request->input()));
    }

    private function getOrCreateManualAction(string $type): ManualAction
    {
        if (! CustomActionModelResolver::isAllowedAction($type)) {
            throw new NotFoundHttpException('not found');
        }

        $manualAction = ManualAction::with('defaultSetting')->firstWhere('type', $type);
        if (! $manualAction) {
            $manualAction = new ManualAction;
            $manualAction->type = $type;
            $manualAction->save();

            $manualAction->setRelation('defaultSetting', null);
        }

        return $manualAction;
    }
}
