<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Exceptions\SimulateActionException;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ManualAction;

trait CallableManuallyTrait
{
    public function getActionModel(): Action
    {
        $type = CustomActionModelResolver::getUniqueName(static::class);
        $action = ManualAction::firstWhere('type', $type);
        if (! $action) {
            throw new \Exception("manual action $type not found");
        }

        return $action;
    }

    /**
     * Ensure the faked class does not modify the database.
     * Otherwise, wrap this call in a non-committed database transaction.
     */
    public static function buildFakeInstance(
        ManualAction $manualAction,
        ?DefaultSetting $setting = null,
        ?LocalizedSetting $localizedSetting = null,
        ?array $state = null,
    ) {
        if (! is_subclass_of(static::class, FakableInterface::class)) {
            $actionUniqueName = CustomActionModelResolver::getUniqueName(static::class);
            throw new SimulateActionException("cannot simulate action, action $actionUniqueName is not fakable");
        }

        $customAction = static::fake($state);
        $customAction->forcedSetting = $setting;
        $customAction->forcedLocalizedSetting = $localizedSetting;

        return $customAction;
    }
}
